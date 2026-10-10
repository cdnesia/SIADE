<?php

namespace App\Http\Controllers;

use App\Services\SinkronPembayaranService;
use Illuminate\Http\Request;

/**
 * Sinkron pembayaran daftar ulang dari tagihan nomor pendaftaran PMB ke tagihan NIM di SIMKEU.
 * Halaman menampilkan pratinjau per NIM dulu, perubahan baru dijalankan saat tombol Sinkronkan ditekan.
 */
class SinkronPembayaranController extends Controller
{
    private $modul = 'mahasiswa-baru.sinkron-pembayaran';

    public function __construct(protected SinkronPembayaranService $service)
    {
        view()->share('modul', $this->modul);
    }

    public function index(Request $request)
    {
        $d['input_nim'] = (string) $request->query('nim', '');
        $d['daftar_nim'] = $this->parseNim($d['input_nim']);
        $d['maks_nim'] = SinkronPembayaranService::MAKS_NIM;
        $d['hasil'] = collect();

        if (count($d['daftar_nim']) > SinkronPembayaranService::MAKS_NIM) {
            session()->now('error', '⚠ Maksimal ' . SinkronPembayaranService::MAKS_NIM . ' NIM sekali periksa.');
        } elseif ($d['daftar_nim']) {
            try {
                $d['hasil'] = $this->service->periksa($d['daftar_nim']);
            } catch (\Exception $e) {
                report($e);
                session()->now('error', '✕ Gagal memuat data tagihan. Periksa koneksi Anda.');
            }
        }

        $d['ringkasan'] = [
            'total' => $d['hasil']->count(),
            'siap' => $d['hasil']->where('status', 'siap')->count(),
            'sudah' => $d['hasil']->where('status', 'sudah')->count(),
            'lainnya' => $d['hasil']->whereNotIn('status', ['siap', 'sudah'])->count(),
        ];

        return view('mahasiswa-baru.sinkron-pembayaran', $d);
    }

    public function sinkron(Request $request)
    {
        $validator = validator($request->all(), [
            'nim' => 'required|array|min:1|max:' . SinkronPembayaranService::MAKS_NIM,
            'nim.*' => 'string|max:20',
        ], [
            'nim.required' => 'Pilih minimal satu NIM.',
            'nim.max' => 'Maksimal ' . SinkronPembayaranService::MAKS_NIM . ' NIM sekali proses.',
        ]);
        if ($validator->fails()) {
            return back()->with('error', '⚠ ' . $validator->errors()->first());
        }

        $berhasil = [];
        $gagal = [];
        foreach (array_unique($request->input('nim')) as $nim) {
            try {
                $this->service->sinkron($nim);
                $berhasil[] = $nim;
            } catch (\RuntimeException $e) {
                $gagal[] = "{$nim} ({$e->getMessage()})";
            } catch (\Exception $e) {
                report($e);
                $gagal[] = "{$nim} (gagal menyimpan)";
            }
        }

        $kembali = redirect()->route($this->modul, ['nim' => $request->input('kembali_nim')]);
        if (!$berhasil) {
            return $kembali->with('error', '✕ Gagal sinkron pembayaran: ' . implode(', ', array_slice($gagal, 0, 3))
                . (count($gagal) > 3 ? ', ...' : ''));
        }

        $pesan = count($berhasil) === 1
            ? "✓ Pembayaran {$berhasil[0]} berhasil disinkron"
            : '✓ ' . count($berhasil) . ' pembayaran berhasil disinkron';
        if ($gagal) {
            $pesan .= ', ' . count($gagal) . ' gagal: ' . implode(', ', array_slice($gagal, 0, 3))
                . (count($gagal) > 3 ? ', ...' : '');
        }

        return $kembali->with('success', $pesan);
    }

    /**
     * NIM bisa dipisah baris baru, koma, spasi, atau titik koma.
     */
    private function parseNim(string $input): array
    {
        return collect(preg_split('/[\s,;]+/', strtoupper($input)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
