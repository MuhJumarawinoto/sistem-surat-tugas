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
        Schema::create('surat_tugas_dinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->onDelete('cascade');
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja')->onDelete('cascade');
            $table->foreignId('kepala_dinas_id')->constrained('users')->onDelete('cascade');

            // Nomor Surat
            $table->string('nomor_surat', 50);
            $table->string('bulan', 20);
            $table->string('tahun', 4);

            // Data Surat
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_ttd');
            $table->string('tempat_ttd', 100)->default('Sukabumi');

            // File
            $table->string('file_path')->nullable();

            // Status
            $table->enum('status', ['draft', 'signed', 'completed'])->default('signed');

            // Timestamps
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['unit_kerja_id', 'nomor_surat', 'tahun'], 'unique_nomor_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_dinas');
    }
};
