<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catat siapa & kapan verifikasi beasiswa dibatalkan.
     */
    public function up(): void
    {
        Schema::table('tbl_verifikasi_beasiswa', function (Blueprint $table) {
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable()->after('diverifikasi_pada');
            $table->timestamp('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_verifikasi_beasiswa', function (Blueprint $table) {
            $table->dropColumn(['dibatalkan_oleh', 'dibatalkan_pada']);
        });
    }
};
