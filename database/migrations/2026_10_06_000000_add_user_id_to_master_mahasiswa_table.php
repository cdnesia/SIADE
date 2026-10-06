<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom user_id sudah ada di production; migrasi ini menyamakan database lain.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('master_mahasiswa', 'user_id')) {
            Schema::table('master_mahasiswa', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Tidak di-drop karena kolom ini bagian dari skema production
    }
};
