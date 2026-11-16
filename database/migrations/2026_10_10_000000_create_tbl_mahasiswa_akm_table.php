<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel tbl_mahasiswa_akm sudah ada di production; migrasi ini membuat tabel
     * jika belum ada dan menambahkan unique index npm + kode_tahun_akademik
     * yang dibutuhkan oleh upsert pada command akm:generate.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tbl_mahasiswa_akm')) {
            Schema::create('tbl_mahasiswa_akm', function (Blueprint $table) {
                $table->id();
                $table->string('kode_tahun_akademik', 10);
                $table->string('npm', 20);
                $table->string('nama_mahasiswa', 250)->nullable();
                $table->string('kode_program_studi', 10);
                $table->unsignedSmallInteger('program_kuliah_id')->nullable();
                $table->unsignedSmallInteger('tagihan_id')->nullable();
                $table->integer('semester')->nullable();
                $table->decimal('ips', 5, 2)->nullable();
                $table->decimal('ipk', 5, 2)->nullable();
                $table->integer('sks_semester')->nullable();
                $table->integer('sks_total')->nullable();
                $table->string('status_mahasiswa', 3)->nullable();
                $table->timestamps();

                $table->unique(['npm', 'kode_tahun_akademik'], 'tbl_mahasiswa_akm_npm_periode_unique');
            });

            return;
        }

        if (!Schema::hasIndex('tbl_mahasiswa_akm', 'tbl_mahasiswa_akm_npm_periode_unique')) {
            $this->bersihkanDuplikat();

            Schema::table('tbl_mahasiswa_akm', function (Blueprint $table) {
                $table->unique(['npm', 'kode_tahun_akademik'], 'tbl_mahasiswa_akm_npm_periode_unique');
            });
        }
    }

    /**
     * Data production bisa berisi lebih dari satu baris untuk npm + periode yang sama
     * (dibuat sebelum unique index ada). Pertahankan baris yang terakhir diubah
     * (updated_at terbaru, lalu id terbesar); sisanya disalin ke tbl_mahasiswa_akm_duplikat
     * sebagai cadangan sebelum dihapus, supaya bisa dicek/dipulihkan.
     */
    private function bersihkanDuplikat(): void
    {
        $adaDuplikat = DB::table('tbl_mahasiswa_akm')
            ->select('npm', 'kode_tahun_akademik')
            ->groupBy('npm', 'kode_tahun_akademik')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (!$adaDuplikat) {
            return;
        }

        // Index biasa sementara agar pencarian duplikat tidak lambat di tabel besar
        $indexSementara = 'tbl_mahasiswa_akm_npm_periode_tmp';
        if (!Schema::hasIndex('tbl_mahasiswa_akm', $indexSementara)) {
            Schema::table('tbl_mahasiswa_akm', fn (Blueprint $table) => $table->index(['npm', 'kode_tahun_akademik'], $indexSementara));
        }

        if (!Schema::hasTable('tbl_mahasiswa_akm_duplikat')) {
            DB::statement('CREATE TABLE tbl_mahasiswa_akm_duplikat LIKE tbl_mahasiswa_akm');
        }

        // Salin baris yang kalah (ada baris lain dengan kunci sama yang lebih baru)
        DB::statement("
            INSERT IGNORE INTO tbl_mahasiswa_akm_duplikat
            SELECT a.* FROM tbl_mahasiswa_akm a
            WHERE EXISTS (
                SELECT 1 FROM tbl_mahasiswa_akm b
                WHERE b.npm = a.npm
                  AND b.kode_tahun_akademik = a.kode_tahun_akademik
                  AND (
                        COALESCE(b.updated_at, '1970-01-01') > COALESCE(a.updated_at, '1970-01-01')
                     OR (COALESCE(b.updated_at, '1970-01-01') = COALESCE(a.updated_at, '1970-01-01') AND b.id > a.id)
                  )
            )
        ");

        DB::statement('DELETE a FROM tbl_mahasiswa_akm a JOIN tbl_mahasiswa_akm_duplikat d ON d.id = a.id');

        Schema::table('tbl_mahasiswa_akm', fn (Blueprint $table) => $table->dropIndex($indexSementara));
    }

    public function down(): void
    {
        // Tidak di-drop karena tabel ini bagian dari skema production
        if (Schema::hasIndex('tbl_mahasiswa_akm', 'tbl_mahasiswa_akm_npm_periode_unique')) {
            Schema::table('tbl_mahasiswa_akm', function (Blueprint $table) {
                $table->dropUnique('tbl_mahasiswa_akm_npm_periode_unique');
            });
        }
    }
};
