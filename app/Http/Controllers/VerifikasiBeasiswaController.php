<?php

namespace App\Http\Controllers;

use App\Models\LembagaBeasiswa;
use App\Models\Mahasiswa;
use App\Models\PenerimaBeasiswa;
use App\Models\TahunAkademik;
use App\Models\VerifikasiBeasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifikasiBeasiswaController extends Controller
{
    private $modul = 'verifikasi-beasiswa';

    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    /**
     * Daftar penerima beasiswa yang terdaftar di semester terpilih,
     * lengkap dengan status verifikasinya.
     */
    public function index(Request $request)
    {
        $tahunAkademik = TahunAkademik::orderByDesc('kode_tahun_akademik')->get();
        $lembaga = LembagaBeasiswa::orderBy('nama_beasiswa')->get();

        // Default: semester yang sedang aktif
        $semester = $request->input('semester')
            ?? $tahunAkademik->firstWhere('status', 'A')->kode_tahun_akademik
            ?? $tahunAkademik->first()->kode_tahun_akademik
            ?? null;
        $idLembaga = $request->input('lembaga');

        $penerima = PenerimaBeasiswa::query()
            ->whereJsonContains('tahun_akademik', (string) $semester)
            ->when($idLembaga, fn($q) => $q->where('id_lembaga', $idLembaga))
            ->orderBy('npm')
            ->get();

        $mahasiswa = Mahasiswa::with('prodi')
            ->whereIn('npm', $penerima->pluck('npm')->unique())
            ->get()
            ->keyBy('npm');

        $verifikasi = VerifikasiBeasiswa::with(['verifikator', 'pembatal'])
            ->where('kode_tahun_akademik', $semester)
            ->whereIn('npm', $penerima->pluck('npm')->unique())
            ->get()
            ->keyBy(fn($v) => $v->npm . '|' . $v->id_lembaga);

        $masterLembaga = $lembaga->keyBy('id');

        $data = $penerima->map(function ($item) use ($mahasiswa, $verifikasi, $masterLembaga) {
            $v = $verifikasi[$item->npm . '|' . $item->id_lembaga] ?? null;
            $l = $masterLembaga[$item->id_lembaga] ?? null;
            return (object) [
                'npm' => $item->npm,
                'id_lembaga' => $item->id_lembaga,
                'nama_mahasiswa' => $mahasiswa[$item->npm]->nama_mahasiswa ?? '-',
                'program_studi' => $mahasiswa[$item->npm]->prodi->nama_program_studi_idn ?? '-',
                'nama_beasiswa' => $l->nama_beasiswa ?? '-',
                'nama_lembaga' => $l->nama_lembaga ?? '-',
                'jenis_tanggungan' => $l->jenis_tanggungan ?? '-',
                'jumlah_jaminan' => $item->jumlah_jaminan,
                'terverifikasi' => (bool) ($v->terverifikasi ?? false),
                'diverifikasi_oleh' => $v?->terverifikasi ? ($v->verifikator->name ?? null) : null,
                'diverifikasi_pada' => $v?->terverifikasi ? $v->diverifikasi_pada : null,
                'dibatalkan_oleh' => $v && !$v->terverifikasi ? ($v->pembatal->name ?? null) : null,
                'dibatalkan_pada' => $v && !$v->terverifikasi ? $v->dibatalkan_pada : null,
            ];
        });

        return view($this->modul . '.view', [
            'tahunAkademik' => $tahunAkademik,
            'lembaga' => $lembaga,
            'semester' => $semester,
            'idLembaga' => $idLembaga,
            'data' => $data,
            'totalTerverifikasi' => $data->where('terverifikasi', true)->count(),
        ]);
    }

    /**
     * Simpan ceklis verifikasi (satu atau banyak baris sekaligus).
     * items[] berformat "npm|id_lembaga", terverifikasi = 1 / 0.
     */
    public function store(Request $request)
    {
        $request->validate([
            'semester' => 'required|string|max:10',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string',
            'terverifikasi' => 'required|boolean',
        ]);

        $semester = $request->semester;
        $status = (bool) $request->terverifikasi;
        $userId = auth()->id();
        $sekarang = now();
        $jumlah = 0;

        // Pecah "npm|id_lembaga" sekali di awal (duplikat dibuang)
        $items = collect($request->items)->unique()->map(function ($item) {
            [$npm, $idLembaga] = array_pad(explode('|', $item, 2), 2, null);
            return ['npm' => $npm, 'id_lembaga' => $idLembaga];
        });

        try {
            DB::transaction(function () use ($items, $semester, $status, $userId, $sekarang, &$jumlah) {
                // Hanya boleh verifikasi jika semester memang terdaftar di data penerima.
                // Dicek dalam satu query (bukan per baris) agar verifikasi massal tetap cepat.
                $terdaftar = PenerimaBeasiswa::query()
                    ->select('npm', 'id_lembaga')
                    ->whereIn('npm', $items->pluck('npm')->unique()->values())
                    ->whereJsonContains('tahun_akademik', (string) $semester)
                    ->get()
                    ->map(fn($p) => $p->npm . '|' . $p->id_lembaga)
                    ->flip();

                $baris = $items
                    ->filter(fn($i) => isset($terdaftar[$i['npm'] . '|' . $i['id_lembaga']]))
                    ->map(fn($i) => [
                        'npm' => $i['npm'],
                        'id_lembaga' => $i['id_lembaga'],
                        'kode_tahun_akademik' => $semester,
                        'terverifikasi' => $status,
                        // Verifikasi: isi verifikator, kosongkan pembatal (dan sebaliknya)
                        'diverifikasi_oleh' => $status ? $userId : null,
                        'diverifikasi_pada' => $status ? $sekarang : null,
                        'dibatalkan_oleh' => $status ? null : $userId,
                        'dibatalkan_pada' => $status ? null : $sekarang,
                    ])
                    ->values();

                // Upsert per 500 baris memakai unique key (npm, id_lembaga, kode_tahun_akademik)
                foreach ($baris->chunk(500) as $potongan) {
                    VerifikasiBeasiswa::upsert(
                        $potongan->all(),
                        ['npm', 'id_lembaga', 'kode_tahun_akademik'],
                        ['terverifikasi', 'diverifikasi_oleh', 'diverifikasi_pada', 'dibatalkan_oleh', 'dibatalkan_pada', 'updated_at']
                    );
                }

                $jumlah = $baris->count();
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Gagal memperbarui data. Coba lagi.'], 500);
        }

        if ($jumlah === 0) {
            return response()->json(['message' => 'Data tidak terdaftar pada semester ini.'], 422);
        }

        return response()->json([
            'message' => $status
                ? "{$jumlah} data berhasil diverifikasi"
                : "Verifikasi {$jumlah} data berhasil dibatalkan",
            'status' => $status,
            'petugas' => auth()->user()->name,
            'waktu' => now()->format('d/m/Y H:i'),
        ]);
    }
}
