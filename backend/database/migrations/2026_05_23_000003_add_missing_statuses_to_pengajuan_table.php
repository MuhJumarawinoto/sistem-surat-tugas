<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL doesn't support altering enum directly, need to recreate the column
        // First, drop the status column and recreate with all values
        \DB::statement("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('draft', 'pending_atasan', 'pending_admin', 'verified', 'disetujui', 'signed', 'ditolak', 'selesai', 'completed', 'dicabut') DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Revert to original enum values
        \DB::statement("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('draft', 'pending_atasan', 'pending_admin', 'disetujui', 'ditolak', 'selesai') DEFAULT 'draft'");
    }
};
