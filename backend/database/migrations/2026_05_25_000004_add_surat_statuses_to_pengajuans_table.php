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
        Schema::table('pengajuan', function (Blueprint $table) {
            // Modify status enum to add new values
            $table->enum('status', [
                'draft',
                'pending_atasan',
                'pending_admin',
                'verified',
                'surat_dinas',
                'surat_izin',
                'disetujui',
                'signed',
                'ditolak',
                'selesai',
                'completed',
                'dicabut'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'pending_atasan',
                'pending_admin',
                'verified',
                'disetujui',
                'signed',
                'ditolak',
                'selesai',
                'completed',
                'dicabut'
            ])->change();
        });
    }
};
