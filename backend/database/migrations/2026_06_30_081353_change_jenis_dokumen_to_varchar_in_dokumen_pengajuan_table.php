<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dokumen_pengajuan', function (Blueprint $table) {
            // Change jenis_dokumen from ENUM to VARCHAR for dynamic values
            $table->string('jenis_dokumen', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_pengajuan', function (Blueprint $table) {
            // Revert back to ENUM (not recommended but for rollback)
            $table->enum('jenis_dokumen', [
                'sk_pangkat',
                'sk_cpns',
                'skp',
                'surat_lulus',
                'jadwal',
                'akreditasi',
                'surat_mandiri',
                'surat_ijazah',
                'surat_sehat',
            ])->change();
        });
    }
};
