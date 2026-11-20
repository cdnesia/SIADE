<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JadwalPertemuan extends Model
{
    protected $table = 'tbl_jadwal_pertemuan';

    public function jadwal()
    {
        return $this->belongsTo(JadwalPerkuliahan::class, 'jadwal_id', 'id');
    }

    /**
     * Setting penilaian pertemuan ini (diambil dari mata kuliah jadwal + nomor pertemuan).
     * Untuk banyak baris, eager load dulu: JadwalPertemuan::with('jadwal.pertemuanNilai').
     */
    public function getSettingNilaiAttribute(): ?KurikulumMataKuliahPertemuan
    {
        return $this->jadwal?->pertemuanNilai->firstWhere('pertemuan', (int) $this->pertemuan);
    }

    public function getBobotNilaiAttribute(): float
    {
        return $this->bobot ?? $this->setting_nilai?->bobot ?? 0;
    }

    /**
     * Tambahkan kolom bobot & jenis_penilaian langsung lewat join (cocok untuk query daftar/laporan).
     * Contoh: JadwalPertemuan::withBobotNilai()->where('tbl_jadwal_pertemuan.jadwal_id', $id)->get()
     */
    public function scopeWithBobotNilai(Builder $query): Builder
    {
        return $query
            ->select('tbl_jadwal_pertemuan.*', 'mp.bobot', 'mp.jenis_penilaian', 'mp.keterangan as keterangan_penilaian')
            ->leftJoin('tbl_jadwal_perkuliahan as jp', 'jp.id', '=', 'tbl_jadwal_pertemuan.jadwal_id')
            ->leftJoin('master_kurikulum_matakuliah_pertemuan as mp', function ($join) {
                $join->on('mp.mata_kuliah_id', '=', 'jp.mata_kuliah_id')
                    ->on('mp.pertemuan', '=', 'tbl_jadwal_pertemuan.pertemuan');
            });
    }
}
