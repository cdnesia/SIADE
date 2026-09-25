<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifikasi penerima beasiswa per semester.
     * Satu baris = satu mahasiswa + satu beasiswa + satu semester.
     * Sistem KRS/tagihan cukup cek: npm + kode_tahun_akademik + terverifikasi = 1.
     */
    public function up(): void
    {
        Schema::create('tbl_verifikasi_beasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('npm', 15);
            $table->integer('id_lembaga');
            $table->string('kode_tahun_akademik', 10);
            $table->boolean('terverifikasi')->default(false);
            $table->unsignedBigInteger('diverifikasi_oleh')->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();

            // Kunci pakai npm + lembaga (bukan id penerima) agar tetap aman
            // saat baris penerima digabung/dihapus oleh proses import.
            $table->unique(['npm', 'id_lembaga', 'kode_tahun_akademik'], 'uniq_verifikasi_beasiswa');
            $table->index(['npm', 'kode_tahun_akademik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_verifikasi_beasiswa');
    }
};
