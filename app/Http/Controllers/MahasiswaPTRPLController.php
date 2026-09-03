<?php

namespace App\Http\Controllers;

use App\Exports\TransferKrsTemplateExport;
use App\Imports\TransferKrsImport;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class MahasiswaPTRPLController extends Controller
{
    private $modul = 'mahasiswa-ptrpl';

    /**
     * Jenis pendaftaran yang termasuk mahasiswa Pindahan/Transfer/RPL.
     * 2 = Pindahan, 13 = RPL Perolehan SKS, 16 = RPL Transfer SKS.
     */
    private const JENIS_PENDAFTARAN_PTRPL = [2, 13, 16];

    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Mahasiswa::from('master_mahasiswa as m')
                ->leftJoin('master_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
                ->leftJoin('master_kelas_perkuliahan as k', 'm.program_kuliah_id', '=', 'k.id')
                ->leftJoin('master_jenis_pendaftaran as jp', 'm.jenis_pendaftaran_id', '=', 'jp.id')
                ->select(
                    'm.*',
                    'p.nama_program_studi_idn as nama_prodi',
                    'k.nama_program_perkuliahan as nama_kelas',
                    'jp.nama_jenis_pendaftaran'
                )
                ->whereIn('m.jenis_pendaftaran_id', self::JENIS_PENDAFTARAN_PTRPL);

            if ($request->prodi) {
                $query->where('m.kode_program_studi', $request->prodi);
            }

            if ($request->tahun) {
                $query->where('m.tahun_angkatan', $request->tahun);
            }

            if ($request->jenis) {
                $query->where('m.jenis_pendaftaran_id', $request->jenis);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('jumlah_krs', function ($row) {
                    return DB::table('tbl_mahasiswa_krs')->where('npm', $row->npm)->count();
                })
                ->addColumn('aksi', function ($row) {
                    return '<a href="' . route('mahasiswa.show', Crypt::encrypt($row->id)) . '?p=krs' . '" class="btn btn-sm btn-info me-1"><i class="bx bx-list-check me-0"></i> Kelola Nilai Transfer</a>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        $prodi = Prodi::orderBy('nama_program_studi_idn')->get();
        $tahun = Mahasiswa::whereIn('jenis_pendaftaran_id', self::JENIS_PENDAFTARAN_PTRPL)
            ->select('tahun_angkatan')
            ->distinct()
            ->orderBy('tahun_angkatan', 'desc')
            ->pluck('tahun_angkatan');
        $jenisPendaftaran = DB::table('master_jenis_pendaftaran')
            ->whereIn('id', self::JENIS_PENDAFTARAN_PTRPL)
            ->get();

        return view($this->modul . '.view', compact('prodi', 'tahun', 'jenisPendaftaran'));
    }

    /**
     * Form untuk import nilai transfer/konversi ke KRS.
     */
    public function importForm()
    {
        return view($this->modul . '.import');
    }

    /**
     * Proses import nilai transfer/konversi dari file Excel ke tbl_mahasiswa_krs.
     */
    public function importStore(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new TransferKrsImport();
        Excel::import($import, $request->file('file'));

        if ($import->berhasil > 0) {
            $pesan = "{$import->berhasil} data nilai transfer berhasil diimport ke KRS.";
            if (!empty($import->errors)) {
                $pesan .= ' ' . count($import->errors) . ' baris dilewati karena error.';
            }
            return redirect()
                ->route($this->modul . '.import')
                ->with('success', $pesan)
                ->with('import_errors', $import->errors);
        }

        return redirect()
            ->route($this->modul . '.import')
            ->with('error', 'Tidak ada data yang berhasil diimport.')
            ->with('import_errors', $import->errors);
    }

    /**
     * Download template Excel untuk import nilai transfer.
     */
    public function downloadTemplate()
    {
        return Excel::download(new TransferKrsTemplateExport(), 'template-nilai-transfer.xlsx');
    }
}
