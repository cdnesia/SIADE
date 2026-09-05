<?php

namespace App\Services;

use App\Models\KRS;
use App\Models\Mahasiswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MasterApiService
{
    public function __construct(protected ApiService $api) {}
    public function expandTerms(int $start, int $end): array
    {
        $y  = intdiv($start, 10);
        $s  = $start % 10;
        $ye = intdiv($end,   10);
        $se = $end   % 10;
        $out = [];
        while ($y < $ye || ($y === $ye && $s <= $se)) {
            $out[] = $y * 10 + $s;
            $s++;
            if ($s > 2) {
                $s = 1;
                $y++;
            }
        }
        return $out;
    }
    public function tahunAkademikAktif($kodeProdi = null)
    {
        $today = Carbon::today()->toDateString();
        $query = DB::table('master_tahun_akademik')
            ->where('status', 'A')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today);
        $query->whereJsonContains('kode_program_studi', $kodeProdi);
        return $query->orderByDesc('id')->value('kode_tahun_akademik');
    }

    public function krs($npm = null)
    {
        $npm = Crypt::decrypt($npm);
        $krsRaw = KRS::with([
            'jadwal',
            'mataKuliahJadwal',
            'mataKuliahLangsung',
            'hari'
        ])
            ->where('npm', $npm)
            ->get();

        $krsRaw = $krsRaw->sortBy([
            fn($a, $b) => $a->kode_tahun_akademik <=> $b->kode_tahun_akademik,
            fn($b, $a) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? '')
        ]);

        $krs = [];
        $total_sks_kumulatif = 0;
        $total_bobot_kumulatif = 0;

        $mahasiswa = Mahasiswa::where('npm', $npm)->firstOrFail();
        $tahun_angkatan = $mahasiswa->tahun_angkatan;
        $kode_program_studi = $mahasiswa->kode_program_studi;
        $tahunAkademikAktif = $this->tahunAkademikAktif($kode_program_studi) ?? $tahun_angkatan;
        $tahunAkademikDitempuh = $this->expandTerms($tahun_angkatan, $tahunAkademikAktif);

        foreach ($tahunAkademikDitempuh as $key => $value) {
            if (!isset($krs[$value])) {
                $krs[$value] = [];
                $krs[$value]['semester'] = $key + 1;
                $krs[$value]['jumlah_sks'] = 0;
                $krs[$value]['total_bobot'] = 0;
                $krs[$value]['krs'] = [];
                $krs[$value]['jumlah_sks'] = 0;
                $krs[$value]['total_bobot'] = 0;
                $krs[$value]['metadata'] = [
                    'ips' => 0,
                    'ipk' => 0,
                ];
            }
            foreach ($krsRaw as $row) {
                $ta = $row['kode_tahun_akademik'];
                if ($value !== (int) $ta) {
                    continue;
                }

                $sks = $row['matakuliah']['sks_mata_kuliah'] ?? 0;
                $bobot = $row['nilai_bobot'] ?? 0;

                $krs[$ta]['jumlah_sks'] += $sks;
                $krs[$ta]['total_bobot'] += $bobot * $sks;

                $total_sks_kumulatif += $sks;
                $total_bobot_kumulatif += $bobot * $sks;

                $krs[$ta]['krs'][] = [
                    'encrypted_id' => Crypt::encrypt($row['id']),
                    'nilai_angka' => $row['nilai_angka'] ?? '',
                    'nilai_huruf' => $row['nilai_huruf'] ?? '',
                    'nilai_bobot' => $bobot,
                    'persetujuan_pa' => $row['persetujuan_pa'] ?? '',
                    'lulus' => $row['lulus'] ?? '',
                    'edome' => $row['edome'] ?? '',
                    'kode_mata_kuliah' => $row['matakuliah']['kode_mata_kuliah'] ?? '',
                    'nama_mata_kuliah' => $row['matakuliah']['nama_mata_kuliah_idn'] ?? '',
                    'sks_matakuliah' => $sks,
                    'jam_mulai' => $row['jadwal']['jam_mulai'] ?? '',
                    'jam_selesai' => $row['jadwal']['jam_selesai'] ?? '',
                    'dosen_id' => $row['jadwal']['dosen_id'] ?? '',
                    'ruang_id' => $row['jadwal']['ruang_id'] ?? '',
                    'kelompok' => $row['jadwal']['kelompok'] ?? '',
                    'hari' => $row['hari']['nama_hari'] ?? '',
                ];

                $krs[$ta]['metadata'] = [
                    'ips' => $krs[$ta]['jumlah_sks'] ? round($krs[$ta]['total_bobot'] / $krs[$ta]['jumlah_sks'], 2) : 0,
                    'ipk' => $total_sks_kumulatif ? round($total_bobot_kumulatif / $total_sks_kumulatif, 2) : 0,
                ];
            }
        }

        return $krs;
    }

    public function bipot()
    {
        return $this->api->get('api/v1/bipot/list');
    }

    public function cetakKhs(string $npm, string $periode)
    {
        return $this->api->postFile('api/v1/khs/cetak', [
            "npm" => $npm,
            "periode" => $periode
        ]);
    }

    public function dataDosen()
    {
        return $this->api->get('api/v1/pegawai/list');
    }

    public function cekTagihanKKN(array $npm)
    {
        return $this->api->post('api/v1/tagihan/cek', ['npm' => $npm, "jenisTagihan" => 'kkn']);
    }
}
