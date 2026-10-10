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
    protected $description = 'Generate Aktivitas Kuliah Mahasiswa (AKM) untuk setiap tahun akademik yang belum tercatat; non-aktif jika tidak ada kontrak KRS. Data yang sudah ada dilewati';

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

            // Ambil penanda AKM yang sudah ada untuk batch ini sekaligus (npm|periode)
            $akmExisting = Akm::whereIn('npm', $npms)
                ->when($kodeTahunAkademik, fn($q) => $q->where('kode_tahun_akademik', $kodeTahunAkademik))
                ->get(['npm', 'kode_tahun_akademik'])
                ->mapWithKeys(fn($akm) => [$akm->npm . '|' . (int) $akm->kode_tahun_akademik => true])
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

                    // Data AKM periode ini sudah ada, lewati tanpa diubah
                    if (isset($akmExisting[$mahasiswa->npm . '|' . $periode])) {
                        $dilewati++;
                        continue;
                    }

                    $sksSemester = $sksPerPeriode[$mahasiswa->npm][$periode]['sks'] ?? 0;
                    $bobotSemester = $sksPerPeriode[$mahasiswa->npm][$periode]['bobot'] ?? 0;
                    $adaKrsPeriodeIni = isset($sksPerPeriode[$mahasiswa->npm][$periode]);

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
                        'status_mahasiswa' => $adaKrsPeriodeIni ? 'A' : 'N',
                        // 'status_mahasiswa' => 'A',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $bar->advance();
            }

            // Simpan sekaligus; insertOrIgnore sebagai pengaman jika ada proses lain yang lebih dulu menyimpan
            foreach (array_chunk($rows, 1000) as $batch) {
                Akm::insertOrIgnore($batch);
            }

            $total += count($rows);
        });

        $bar->finish();
        $this->newLine();
        $this->info("Selesai. Data AKM baru: {$total} (sudah ada, dilewati: {$dilewati})");
    }
}
