<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengajuan table indexes
        Schema::table('pengajuan', function (Blueprint $table) {
            // Composite index for user + status (common query pattern)
            $table->index(['user_id', 'status'], 'idx_user_status');

            // Index for status filtering
            $table->index('status', 'idx_status');

            // Index for created_at sorting
            $table->index('created_at', 'idx_created_at');

            // Index for jenjang_id filtering
            $table->index('jenjang_id', 'idx_jenjang');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for unit_kerja filtering (atasan approval query)
            $table->index('unit_kerja_id', 'idx_unit_kerja');

            // Index for jabatan_kategori filtering
            $table->index('jabatan_kategori', 'idx_jabatan_kategori');

            // Composite index for atasan lookup
            $table->index('atasan_id', 'idx_atasan');
        });

        // Dokumen pengajuan table indexes
        Schema::table('dokumen_pengajuan', function (Blueprint $table) {
            // Composite index for pengajuan + jenis
            $table->index(['pengajuan_id', 'jenis_dokumen'], 'idx_pengajuan_jenis');

            // Index for status_verifikasi filtering
            $table->index('status_verifikasi', 'idx_status_verifikasi');
        });

        // Unit kerja table indexes
        Schema::table('unit_kerja', function (Blueprint $table) {
            // Index for active filtering
            $table->index('is_active', 'idx_is_active');

            // Index for nama sorting
            $table->index('nama', 'idx_nama');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_jenjang');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_unit_kerja');
            $table->dropIndex('idx_jabatan_kategori');
            $table->dropIndex('idx_atasan');
        });

        Schema::table('dokumen_pengajuan', function (Blueprint $table) {
            $table->dropIndex('idx_pengajuan_jenis');
            $table->dropIndex('idx_status_verifikasi');
        });

        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->dropIndex('idx_is_active');
            $table->dropIndex('idx_nama');
        });
    }
};
