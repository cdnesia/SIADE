<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catat siapa & kapan verifikasi beasiswa dibatalkan.
     * Kolom dicek dulu karena di production sudah ada sebelum migrasi ini tercatat.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_verifikasi_beasiswa', 'dibatalkan_oleh')) {
            Schema::table('tbl_verifikasi_beasiswa', function (Blueprint $table) {
                $table->unsignedBigInteger('dibatalkan_oleh')->nullable()->after('diverifikasi_pada');
            });
        }

        if (! Schema::hasColumn('tbl_verifikasi_beasiswa', 'dibatalkan_pada')) {
            Schema::table('tbl_verifikasi_beasiswa', function (Blueprint $table) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tbl_verifikasi_beasiswa', function (Blueprint $table) {
            $table->dropColumn(['dibatalkan_oleh', 'dibatalkan_pada']);
        });
    }
};
