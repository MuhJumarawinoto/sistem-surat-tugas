<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->enum('jenis_dokumen', [
                'sk_pangkat',
                'sk_cpns',
                'skp',
                'surat_lulus',
                'jadwal',
                'akreditasi',
                'surat_mandiri',
                'surat_ijazah',
                'surat_sehat'
            ]);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->enum('status_verifikasi', ['pending', 'lengkap', 'tidak_lengkap'])->default('pending');
            $table->text('catatan')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuan');
    }
};
