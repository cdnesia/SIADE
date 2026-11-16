<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cadangan baris tbl_mahasiswa_krs yang dihapus dari menu Wisuda (matakuliah dobel),
     * supaya bisa dilacak siapa yang menghapus dan dipulihkan jika salah hapus.
     */
    public function up(): void
    {
        Schema::create('tbl_wisuda_hapus_krs_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('krs_id');
            $table->string('npm', 30)->index();
            $table->json('data_krs');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_wisuda_hapus_krs_log');
    }
};
