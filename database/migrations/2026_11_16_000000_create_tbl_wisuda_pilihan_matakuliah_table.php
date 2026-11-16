<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan pilihan matakuliah dobel yang dipakai di transkrip wisuda.
     * Setiap anggota grup dobel punya satu baris: diambil = Y untuk yang dihitung,
     * T untuk yang tidak. Data KRS asli tidak diubah sama sekali.
     */
    public function up(): void
    {
        Schema::create('tbl_wisuda_pilihan_matakuliah', function (Blueprint $table) {
            $table->id();
            $table->string('npm', 30)->index();
            $table->unsignedBigInteger('krs_id');
            $table->enum('diambil', ['Y', 'T'])->default('T');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['npm', 'krs_id'], 'tbl_wisuda_pilihan_npm_krs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_wisuda_pilihan_matakuliah');
    }
};
