<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monitoring perkuliahan oleh kaprodi/sekpro (dipakai Laravel SIKAP lewat REST-API-SIKAP).
 * Ditulis HANYA oleh REST-API-SIKAP
 * (App\Services\VerifikasiProdiService); kolom datetime_* = UTC.
 *
 * - tbl_jadwal_pertemuan_verifikasi_prodi: status verifikasi prodi TERKINI per pertemuan (tanpa baris = MENUNGGU).
 *   Hanya untuk MK master_kurikulum_matakuliah.is_penilaian_obe = 1. DISETUJUI = berita acara & absensi dosen dikunci.
 * - tbl_jadwal_pertemuan_komentar: diskusi prodi ↔ dosen + jejak aksi verifikasi.
 *
 * Tanpa foreign key (sama seperti tbl_mahasiswa_nilai_pertemuan) agar tidak bergantung pada tipe id tabel lama.
 * hasTable(): aman bila tabel sudah ada (mis. dibuat manual sebelumnya).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_jadwal_pertemuan_verifikasi_prodi')) {
            Schema::create('tbl_jadwal_pertemuan_verifikasi_prodi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jadwal_id')->comment('tbl_jadwal_perkuliahan.id');
                $table->unsignedBigInteger('jadwal_pertemuan_id')->comment('tbl_jadwal_pertemuan.id');
                $table->enum('status', ['MENUNGGU', 'DISETUJUI', 'PERLU_PERBAIKAN'])->default('MENUNGGU');
                $table->text('catatan')->nullable()->comment('catatan peninjau (wajib untuk PERLU_PERBAIKAN)');
                $table->unsignedBigInteger('pegawai_id')->comment('UMJA pegawai.id peninjau terakhir');
                $table->enum('peran', ['KAPRODI', 'SEKPRO']);
                $table->dateTime('datetime_tinjau')->comment('UTC');
                $table->dateTime('datetime_diperbaiki')->nullable()
                    ->comment('UTC — dosen mengubah data setelah PERLU_PERBAIKAN (status kembali MENUNGGU)');

                $table->unique('jadwal_pertemuan_id', 'uq_verifikasi_prodi_pertemuan');
                $table->index('jadwal_id', 'idx_verifikasi_prodi_jadwal');
            });
        }

        if (! Schema::hasTable('tbl_jadwal_pertemuan_komentar')) {
            Schema::create('tbl_jadwal_pertemuan_komentar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jadwal_id')->comment('tbl_jadwal_perkuliahan.id');
                $table->unsignedBigInteger('jadwal_pertemuan_id')->comment('tbl_jadwal_pertemuan.id');
                $table->unsignedBigInteger('pegawai_id')->comment('UMJA pegawai.id penulis');
                $table->enum('peran', ['KAPRODI', 'SEKPRO', 'DOSEN']);
                $table->enum('jenis', ['KOMENTAR', 'DISETUJUI', 'PERLU_PERBAIKAN', 'DIBATALKAN', 'DIPERBAIKI'])->default('KOMENTAR');
                $table->text('isi')->nullable()->comment('teks komentar / catatan aksi');
                $table->dateTime('datetime_created')->comment('UTC');

                $table->index(['jadwal_pertemuan_id', 'id'], 'idx_komentar_pertemuan');
                $table->index('jadwal_id', 'idx_komentar_jadwal');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_jadwal_pertemuan_komentar');
        Schema::dropIfExists('tbl_jadwal_pertemuan_verifikasi_prodi');
    }
};
