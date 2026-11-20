<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setting penilaian per pertemuan untuk tiap mata kuliah kurikulum.
 * Jadwal (tbl_jadwal_perkuliahan) mengambil bobotnya lewat mata_kuliah_id + nomor pertemuan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_kurikulum_matakuliah_pertemuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')
                ->constrained('master_kurikulum_matakuliah')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->string('jenis_penilaian', 20)->default('TIDAK_DINILAI')
                ->comment('TUGAS, KUIS, UTS, UAS, PRAKTIKUM, PROYEK, PRESENTASI, KEHADIRAN, TIDAK_DINILAI');
            $table->decimal('bobot', 5, 2)->default(0)->comment('Persentase terhadap nilai akhir (total per MK = 100)');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['mata_kuliah_id', 'pertemuan'], 'mk_pertemuan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kurikulum_matakuliah_pertemuan');
    }
};
