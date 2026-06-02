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
        Schema::create('surat_izin_belajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->onDelete('cascade');
            $table->foreignId('surat_tugas_dinas_id')->constrained('surat_tugas_dinas')->onDelete('cascade');

            // Nomor Surat
            $table->string('nomor_surat', 100);
            $table->string('tahun', 4);

            // File
            $table->string('file_path')->nullable();
            $table->string('tte_path')->nullable();
            $table->string('qr_code')->nullable();

            // Status
            $table->enum('status', ['draft', 'signed', 'completed'])->default('draft');

            // Timestamps
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique('nomor_surat', 'unique_nomor_surat_izin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_izin_belajar');
    }
};
