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
        Schema::create('surat_tugas_mandiri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->onDelete('cascade');
            $table->foreignId('surat_izin_belajar_id')->constrained('surat_izin_belajar')->onDelete('cascade');
            $table->foreignId('surat_tugas_dinas_id')->nullable()->constrained('surat_tugas_dinas')->onDelete('set null');

            // Nomor Surat
            $table->string('nomor_surat');
            $table->string('tahun', 4);

            // Data Surat
            $table->date('tanggal_surat');
            $table->string('tempat_ttd')->default('Sukabumi');

            // File
            $table->string('file_path')->nullable();
            $table->string('tte_path')->nullable();
            $table->text('qr_code')->nullable();

            // Status
            $table->enum('status', ['draft', 'signed', 'completed'])->default('draft');

            // Timestamps
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('signed_by_nip')->nullable();

            $table->timestamps();

            // Unique nomor surat per tahun
            $table->unique('nomor_surat', 'unique_nomor_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_mandiri');
    }
};
