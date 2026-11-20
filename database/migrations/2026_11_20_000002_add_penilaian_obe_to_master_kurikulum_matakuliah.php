<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ceklis "Penilaian OBE" per mata kuliah + tempat menyimpan nilai mahasiswa per pertemuan.
 * - is_penilaian_obe = 1 : nilai akhir dihitung dari bobot per pertemuan (master_kurikulum_matakuliah_pertemuan)
 * - is_penilaian_obe = 0 : tetap memakai komponen lama (sikap, kuis, uts, ku, kh, uas)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_kurikulum_matakuliah', function (Blueprint $table) {
            $table->boolean('is_penilaian_obe')->default(false)->after('is_mk_universitas')
                ->comment('1 = nilai akhir dari bobot per pertemuan (OBE), 0 = komponen lama');
        });

        Schema::create('tbl_mahasiswa_nilai_pertemuan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->string('npm', 30);
            $table->unsignedTinyInteger('pertemuan');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['jadwal_id', 'npm', 'pertemuan'], 'nilai_pertemuan_unique');
            $table->index('npm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mahasiswa_nilai_pertemuan');
        Schema::table('master_kurikulum_matakuliah', function (Blueprint $table) {
            $table->dropColumn('is_penilaian_obe');
        });
    }
};
