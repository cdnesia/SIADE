<?php

namespace App\Http\Controllers;

use App\Exports\MahasiswaBaruTemplateExport;
use App\Imports\MahasiswaBaruImport;
use App\Services\PenmaruMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import mahasiswa baru dari file Excel ke master_mahasiswa (beserta akun loginnya).
 * File diunggah lalu divalidasi sebagai pratinjau dulu, data baru disimpan saat tombol Simpan ditekan.
 */
class ImportMahasiswaBaruController extends Controller
{
    private $modul = 'mahasiswa-baru.import';

    /** Lama pratinjau disimpan sebelum harus unggah ulang */
    const MENIT_PRATINJAU = 30;

    public function __construct(protected PenmaruMahasiswaService $penmaru)
    {
        view()->share('modul', $this->modul);
    }

    public function index()
    {
        $d['pratinjau'] = $this->ambilPratinjau();
        $d['maks_baris'] = MahasiswaBaruImport::MAKS_BARIS;
        $d['prodi'] = DB::table('master_program_studi')->orderBy('nama_program_studi_idn')->get(['kode_program_studi', 'nama_program_studi_idn']);
        $d['kelas'] = DB::table('master_kelas_perkuliahan')->orderBy('id')->get(['id', 'nama_program_perkuliahan']);
        $d['jenis_pendaftaran'] = DB::table('master_jenis_pendaftaran')->orderBy('id')->get(['id', 'nama_jenis_pendaftaran']);

        return view($this->modul, $d);
    }

    public function preview(Request $request)
    {
        $validator = validator($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', '⚠ Periksa kembali isian form Anda.');
        }

        $import = new MahasiswaBaruImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', '✕ File tidak dapat dibaca. Pastikan memakai template yang disediakan.');
        }

        if ($import->melebihiBatas) {
            return back()->with('error', "✕ File berisi {$import->total} baris, maksimal " . MahasiswaBaruImport::MAKS_BARIS . ' baris sekali import.');
        }
        if ($import->total === 0) {
            return back()->with('error', '✕ File tidak berisi data mahasiswa.');
        }

        $this->hapusPratinjau();
        $kunci = 'import-mahasiswa-baru:' . Str::uuid();
        Cache::put($kunci, [
            'nama_file' => $request->file('file')->getClientOriginalName(),
            'total' => $import->total,
            'valid' => $import->valid,
            'errors' => $import->errors,
        ], now()->addMinutes(self::MENIT_PRATINJAU));
        session(['import_mahasiswa_baru' => $kunci]);

        $jumlahValid = count($import->valid);
        $jumlahError = count($import->errors);

        if ($jumlahValid === 0) {
            return redirect()->route($this->modul)->with('error', "⚠ Semua {$jumlahError} baris bermasalah. Perbaiki file lalu unggah ulang.");
        }

        return redirect()->route($this->modul)->with('success', "File terbaca: {$jumlahValid} baris siap disimpan"
            . ($jumlahError ? ", {$jumlahError} baris bermasalah" : '') . '. Periksa pratinjau lalu klik Simpan.');
    }

    public function simpan()
    {
        $pratinjau = $this->ambilPratinjau();
        if (!$pratinjau) {
            return redirect()->route($this->modul)->with('error', '✕ Pratinjau sudah kedaluwarsa. Silakan unggah ulang file.');
        }

        $baris = array_column($pratinjau['valid'], 'data');

        // Cek ulang NPM/NIK, bisa saja sudah ditambahkan orang lain sejak pratinjau dibuat
        $npmTerpakai = DB::table('master_mahasiswa')->whereIn('npm', array_column($baris, 'npm'))->pluck('npm')->flip();
        $nikTerpakai = DB::table('master_mahasiswa')->whereIn('nik', array_filter(array_column($baris, 'nik')))->pluck('nik')->flip();

        $dilewati = [];
        $simpan = [];
        foreach ($baris as $b) {
            if (isset($npmTerpakai[$b['npm']]) || ($b['nik'] && isset($nikTerpakai[$b['nik']]))) {
                $dilewati[] = $b['npm'];
                continue;
            }
            $simpan[] = $b + [
                // Kolom wajib di tabel yang tidak ada di file, nilainya mengikuti import dari PMB
                'va_code' => '',
                'pa_id' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!$simpan) {
            $this->hapusPratinjau();
            return redirect()->route($this->modul)->with('error', '✕ Gagal menambahkan data. Semua NPM/NIK sudah terdaftar.');
        }

        try {
            $this->penmaru->simpanKeMaster($simpan);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route($this->modul)->with('error', '✕ Gagal menambahkan data. Coba lagi.');
        }

        $this->hapusPratinjau();

        $pesan = '✓ Data berhasil ditambahkan. ' . count($simpan) . ' mahasiswa diimport, akun login dibuat dengan password = NPM.';
        if ($dilewati) {
            $pesan .= ' ' . count($dilewati) . ' dilewati karena NPM/NIK sudah terdaftar: ' . implode(', ', array_slice($dilewati, 0, 10))
                . (count($dilewati) > 10 ? ', ...' : '');
        }

        return redirect()->route($this->modul)
            ->with('success', $pesan)
            ->with('import_selesai', ['disimpan' => count($simpan), 'dilewati' => $dilewati]);
    }

    public function batal()
    {
        $this->hapusPratinjau();

        return redirect()->route($this->modul);
    }

    public function template()
    {
        return Excel::download(new MahasiswaBaruTemplateExport(), 'template-import-mahasiswa-baru.xlsx');
    }

    private function ambilPratinjau(): ?array
    {
        $kunci = session('import_mahasiswa_baru');

        return $kunci ? Cache::get($kunci) : null;
    }

    private function hapusPratinjau(): void
    {
        if ($kunci = session()->pull('import_mahasiswa_baru')) {
            Cache::forget($kunci);
        }
    }
}
