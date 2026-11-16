<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat perbaikan nilai dari menu Wisuda. Nilai baru langsung ditulis ke
     * tbl_mahasiswa_krs; tabel ini menyimpan nilai lama agar bisa dilacak/dipulihkan.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_wisuda_perbaikan_nilai_log')) {
            return;
        }

        Schema::create('tbl_wisuda_perbaikan_nilai_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('krs_id')->index();
            $table->string('npm', 30)->index();
            $table->decimal('nilai_angka_lama', 5, 2)->nullable();
            $table->string('nilai_huruf_lama', 5)->nullable();
            $table->decimal('nilai_bobot_lama', 5, 2)->nullable();
            $table->decimal('nilai_angka_baru', 5, 2);
            $table->string('nilai_huruf_baru', 5);
            $table->decimal('nilai_bobot_baru', 5, 2);
            $table->string('jenis', 20); // isi_kosong | perbaikan
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_wisuda_perbaikan_nilai_log');
    }
};
