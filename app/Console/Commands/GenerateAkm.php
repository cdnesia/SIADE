<?php

namespace App\Console\Commands;

use App\Models\Akm;
use App\Models\KRS;
use App\Models\Mahasiswa;
use App\Services\MasterApiService;
use Illuminate\Console\Command;

class GenerateAkm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'akm:generate
        {kode_tahun_akademik? : Kode tahun akademik spesifik (opsional, default semua periode sejak angkatan sampai periode aktif tiap mahasiswa)}
        {--chunk=500 : Jumlah mahasiswa yang diproses per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate/perbarui status Aktivitas Kuliah Mahasiswa (AKM) untuk setiap tahun akademik; non-aktif jika tidak ada kontrak KRS';

    /**
     * Status yang boleh ditimpa otomatis oleh generator. Status di luar ini
     * dianggap hasil input manual admin (mis. Cuti/DO) dan tidak akan ditimpa.
     */
    private const STATUS_OTOMATIS = ['A', 'N'];

    /**
     * Kolom yang diperbarui saat baris AKM sudah ada (tagihan_id sengaja tidak ikut).
     */
    private const KOLOM_UPDATE = [
        'nama_mahasiswa',
        'kode_program_studi',
        'program_kuliah_id',
        'semester',
        'ips',
        'ipk',
        'sks_semester',
        'sks_total',
        'status_mahasiswa',
        'updated_at',
    ];

    /**
     * Cache periode aktif per prodi agar tidak query berulang per mahasiswa.
     */
    private array $periodeAktifProdi = [];

    public function handle(MasterApiService $dataService)
    {
        set_time_limit(0);

        $kodeTahunAkademik = $this->argument('kode_tahun_akademik');
        $ukuranChunk = max(1, (int) $this->option('chunk'));

        $mahasiswas = Mahasiswa::query()
            ->select(['id', 'npm', 'nama_mahasiswa', 'kode_program_studi', 'program_kuliah_id', 'tahun_angkatan'])
            ->when($kodeTahunAkademik, fn($q) => $q->where('tahun_angkatan', '<=', $kodeTahunAkademik));

        $bar = $this->output->createProgressBar((clone $mahasiswas)->count());
        $bar->start();

        $total = 0;
        $dilewati = 0;

        $mahasiswas->chunkById($ukuranChunk, function ($chunk) use ($dataService, $kodeTahunAkademik, &$total, &$dilewati, $bar) {
            $npms = $chunk->pluck('npm')->all();

            // Ambil KRS seluruh mahasiswa di batch ini sekaligus, lalu ringkas per npm + periode
            $sksPerPeriode = [];
            KRS::with(['mataKuliahJadwal', 'mataKuliahLangsung'])
                ->whereIn('npm', $npms)
                ->get()
                ->each(function ($row) use (&$sksPerPeriode) {
                    $sks = $row->mata_kuliah->sks_mata_kuliah ?? 0;
                    $bobot = $row->nilai_bobot ?? 0;
                    $periode = (int) $row->kode_tahun_akademik;

                    $sksPerPeriode[$row->npm][$periode]['sks'] = ($sksPerPeriode[$row->npm][$periode]['sks'] ?? 0) + $sks;
                    $sksPerPeriode[$row->npm][$periode]['bobot'] = ($sksPerPeriode[$row->npm][$periode]['bobot'] ?? 0) + $bobot * $sks;
                });

            // Ambil status AKM yang sudah ada untuk batch ini sekaligus
            $statusExisting = Akm::whereIn('npm', $npms)
                ->when($kodeTahunAkademik, fn($q) => $q->where('kode_tahun_akademik', $kodeTahunAkademik))
                ->get(['npm', 'kode_tahun_akademik', 'status_mahasiswa'])
                ->mapWithKeys(fn($akm) => [$akm->npm . '|' . (int) $akm->kode_tahun_akademik => $akm->status_mahasiswa])
                ->all();

            $now = now();
            $rows = [];

            foreach ($chunk as $mahasiswa) {
                $prodi = $mahasiswa->kode_program_studi;
                if (!array_key_exists($prodi, $this->periodeAktifProdi)) {
                    $this->periodeAktifProdi[$prodi] = $dataService->tahunAkademikAktif($prodi);
                }
                $tahunAkademikAktif = $this->periodeAktifProdi[$prodi] ?? $mahasiswa->tahun_angkatan;

                $periodesMahasiswa = $dataService->expandTerms(
                    (int) $mahasiswa->tahun_angkatan,
                    (int) $tahunAkademikAktif
                );

                $krsMahasiswa = $sksPerPeriode[$mahasiswa->npm] ?? [];
                $sksTotal = 0;
                $bobotTotal = 0;

                foreach ($periodesMahasiswa as $index => $periode) {
                    // Akumulasi KRS sampai periode ini (termasuk KRS sebelum angkatan, jika ada)
                    foreach ($krsMahasiswa as $periodeKrs => $nilai) {
                        if ($periodeKrs <= $periode) {
                            $sksTotal += $nilai['sks'];
                            $bobotTotal += $nilai['bobot'];
                            unset($krsMahasiswa[$periodeKrs]);
                        }
                    }

                    if ($kodeTahunAkademik && $periode !== (int) $kodeTahunAkademik) {
                        continue;
                    }

                    $sksSemester = $sksPerPeriode[$mahasiswa->npm][$periode]['sks'] ?? 0;
                    $bobotSemester = $sksPerPeriode[$mahasiswa->npm][$periode]['bobot'] ?? 0;
                    $adaKrsPeriodeIni = isset($sksPerPeriode[$mahasiswa->npm][$periode]);

                    $statusLama = $statusExisting[$mahasiswa->npm . '|' . $periode] ?? null;
                    $statusOverride = $statusLama !== null && !in_array($statusLama, self::STATUS_OTOMATIS, true);

                    $rows[] = [
                        'npm' => $mahasiswa->npm,
                        'kode_tahun_akademik' => $periode,
                        'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                        'kode_program_studi' => $mahasiswa->kode_program_studi,
                        'program_kuliah_id' => $mahasiswa->program_kuliah_id,
                        'tagihan_id' => 0,
                        'semester' => $index + 1,
                        'ips' => $sksSemester ? round($bobotSemester / $sksSemester, 2) : 0,
                        'ipk' => $sksTotal ? round($bobotTotal / $sksTotal, 2) : 0,
                        'sks_semester' => $sksSemester,
                        'sks_total' => $sksTotal,
                        // 'status_mahasiswa' => $statusOverride ? $statusLama : ($adaKrsPeriodeIni ? 'A' : 'N'),
                        'status_mahasiswa' => 'A',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($statusOverride) {
                        $dilewati++;
                    }
                }

                $bar->advance();
            }

            // Simpan sekaligus; baris yang sudah ada diperbarui (butuh unique index npm + kode_tahun_akademik)
            foreach (array_chunk($rows, 1000) as $batch) {
                Akm::upsert($batch, ['npm', 'kode_tahun_akademik'], self::KOLOM_UPDATE);
            }

            $total += count($rows);
        });

        $bar->finish();
        $this->newLine();
        $this->info("Selesai. Total data AKM diperbarui: {$total} (status manual dipertahankan: {$dilewati})");
    }
}
