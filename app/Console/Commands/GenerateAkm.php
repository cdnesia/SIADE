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
    protected $signature = 'akm:generate {kode_tahun_akademik? : Kode tahun akademik spesifik (opsional, default semua periode sejak angkatan sampai periode aktif tiap mahasiswa)}';

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

    public function handle(MasterApiService $dataService)
    {
        set_time_limit(0);

        $kodeTahunAkademik = $this->argument('kode_tahun_akademik');

        $mahasiswas = Mahasiswa::query();
        if ($kodeTahunAkademik) {
            $mahasiswas->where('tahun_angkatan', '<=', $kodeTahunAkademik);
        }
        $mahasiswas = $mahasiswas->get();

        $bar = $this->output->createProgressBar($mahasiswas->count());
        $bar->start();

        $total = 0;
        $dilewati = 0;

        foreach ($mahasiswas as $mahasiswa) {
            $tahunAkademikAktif = $dataService->tahunAkademikAktif($mahasiswa->kode_program_studi) ?? $mahasiswa->tahun_angkatan;

            $periodesMahasiswa = $dataService->expandTerms(
                (int) $mahasiswa->tahun_angkatan,
                (int) $tahunAkademikAktif
            );

            $periodesDiproses = $kodeTahunAkademik
                ? array_values(array_filter($periodesMahasiswa, fn($p) => $p === (int) $kodeTahunAkademik))
                : $periodesMahasiswa;

            if (empty($periodesDiproses)) {
                $bar->advance();
                continue;
            }

            $krsRaw = KRS::with(['mataKuliahJadwal', 'mataKuliahLangsung'])
                ->where('npm', $mahasiswa->npm)
                ->get();

            foreach ($periodesDiproses as $periode) {
                $semester = count($dataService->expandTerms((int) $mahasiswa->tahun_angkatan, (int) $periode));

                $sksSemester = 0;
                $bobotSemester = 0;
                $sksTotal = 0;
                $bobotTotal = 0;
                $adaKrsPeriodeIni = false;

                foreach ($krsRaw as $row) {
                    $sks = $row->mata_kuliah->sks_mata_kuliah ?? 0;
                    $bobot = $row->nilai_bobot ?? 0;

                    if ((int) $row->kode_tahun_akademik <= (int) $periode) {
                        $sksTotal += $sks;
                        $bobotTotal += $bobot * $sks;
                    }
                    if ((int) $row->kode_tahun_akademik === (int) $periode) {
                        $sksSemester += $sks;
                        $bobotSemester += $bobot * $sks;
                        $adaKrsPeriodeIni = true;
                    }
                }

                $existing = Akm::where('npm', $mahasiswa->npm)
                    ->where('kode_tahun_akademik', $periode)
                    ->first();

                $statusOverride = $existing && !in_array($existing->status_mahasiswa, self::STATUS_OTOMATIS, true);
                $status = $statusOverride
                    ? $existing->status_mahasiswa
                    : ($adaKrsPeriodeIni ? 'A' : 'N');

                Akm::updateOrCreate(
                    [
                        'npm' => $mahasiswa->npm,
                        'kode_tahun_akademik' => $periode,
                    ],
                    [
                        'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                        'kode_program_studi' => $mahasiswa->kode_program_studi,
                        'program_kuliah_id' => $mahasiswa->program_kuliah_id,
                        'tagihan_id' => $existing->tagihan_id ?? 0,
                        'semester' => $semester,
                        'ips' => $sksSemester ? round($bobotSemester / $sksSemester, 2) : 0,
                        'ipk' => $sksTotal ? round($bobotTotal / $sksTotal, 2) : 0,
                        'sks_semester' => $sksSemester,
                        'sks_total' => $sksTotal,
                        'status_mahasiswa' => $status,
                    ]
                );

                if ($statusOverride) {
                    $dilewati++;
                }

                $total++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Selesai. Total data AKM diperbarui: {$total} (status manual dipertahankan: {$dilewati})");
    }
}
