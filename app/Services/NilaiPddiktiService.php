<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Mengambil nilai mahasiswa yang tercatat di PDDikti (lewat Neo Feeder) untuk
 * DIBANDINGKAN dengan KRS SIADE. Transkrip & IPK tetap dihitung dari SIADE;
 * nilai PDDikti hanya disalin ke KRS SIADE jika pengguna menekan tombol sinkron.
 */
class NilaiPddiktiService
{
    private const LAMA_CACHE = 600; // detik

    public function __construct(protected NeofeederService $feeder) {}

    /**
     * Nilai PDDikti satu mahasiswa, dikelompokkan per kode MK (huruf besar).
     * Satu kode bisa punya beberapa baris (mis. mengulang di periode lain).
     *
     * @return Collection<string, Collection<int, array{periode: string, kode_mk: string, nama_mk: string, sks: float, kelas: ?string, nilai_angka: ?float, nilai_huruf: ?string, nilai_indeks: ?float}>>
     *
     * @throws RuntimeException jika Feeder gagal dihubungi
     */
    public function nilaiMahasiswa(string $npm, bool $segarkan = false): Collection
    {
        $npm = preg_replace('/[^0-9A-Za-z]/', '', $npm);
        $kunci = "pddikti.nilai.$npm";
        if ($segarkan) {
            Cache::forget($kunci);
        }

        $data = Cache::remember($kunci, self::LAMA_CACHE, function () use ($npm) {
            $registrasi = $this->panggil('GetListRiwayatPendidikanMahasiswa', "nim='$npm'");
            if (empty($registrasi)) {
                return [];
            }

            $nilai = [];
            foreach ($registrasi as $reg) {
                $idReg = preg_replace('/[^0-9a-f-]/i', '', $reg['id_registrasi_mahasiswa']);
                $nilai = array_merge($nilai, $this->panggil('GetRiwayatNilaiMahasiswa', "id_registrasi_mahasiswa='$idReg'"));
            }
            if (empty($nilai)) {
                return [];
            }

            // Riwayat nilai tidak memuat kode MK, jadi ambil kode dari daftar matakuliah Feeder
            $idMatkul = collect($nilai)->pluck('id_matkul')->filter()->unique()
                ->map(fn ($id) => "'".preg_replace('/[^0-9a-f-]/i', '', $id)."'")->implode(',');
            $kode = collect($this->panggil('GetListMataKuliah', "id_matkul in ($idMatkul)"))
                ->pluck('kode_mata_kuliah', 'id_matkul');

            return collect($nilai)->map(fn ($n) => [
                'periode' => (string) $n['id_periode'],
                'kode_mk' => strtoupper(trim((string) ($kode[$n['id_matkul']] ?? ''))),
                'nama_mk' => $n['nama_mata_kuliah'],
                'sks' => (float) $n['sks_mata_kuliah'],
                'kelas' => $n['nama_kelas_kuliah'] ?? null,
                'nilai_angka' => $n['nilai_angka'] !== null && $n['nilai_angka'] !== '' ? round((float) $n['nilai_angka'], 2) : null,
                'nilai_huruf' => $n['nilai_huruf'] !== null ? trim($n['nilai_huruf']) : null,
                'nilai_indeks' => $n['nilai_indeks'] !== null && $n['nilai_indeks'] !== '' ? (float) $n['nilai_indeks'] : null,
            ])->filter(fn ($n) => $n['kode_mk'] !== '')->values()->all();
        });

        return collect($data)
            ->sortBy('periode')
            ->groupBy('kode_mk')
            ->map(fn ($g) => $g->values());
    }

    /**
     * Satu baris nilai PDDikti untuk kode MK + periode (dipakai saat menyimpan,
     * supaya nilai yang disalin diambil dari server, bukan dari isian browser).
     */
    public function baris(string $npm, string $kodeMk, string $periode): ?array
    {
        return $this->nilaiMahasiswa($npm)->get(strtoupper(trim($kodeMk)), collect())
            ->firstWhere('periode', $periode);
    }

    private function panggil(string $aksi, string $filter): array
    {
        $parameter = ['act' => $aksi, 'filter' => $filter, 'order' => '', 'limit' => 0, 'offset' => 0];

        // Feeder kadang timeout saat minta token, jadi coba sekali lagi sebelum menyerah
        $hasil = $this->feeder->getData($parameter);
        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            $hasil = $this->feeder->getData($parameter);
        }
        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            throw new RuntimeException("Neo Feeder $aksi gagal: ".($hasil['error_desc'] ?? 'tanpa keterangan'));
        }

        return $hasil['data'] ?? [];
    }
}
