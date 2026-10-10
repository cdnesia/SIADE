<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            Schema::table('tbl_mahasiswa_akm', function (Blueprint $table) {
                $table->unique(['npm', 'kode_tahun_akademik'], 'tbl_mahasiswa_akm_npm_periode_unique');
            });
        }
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
