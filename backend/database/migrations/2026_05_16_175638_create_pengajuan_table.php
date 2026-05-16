<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengajuan')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jenjang_id')->constrained('jenjang_pendidikan')->restrictOnDelete();
            $table->string('nama_prodi');
            $table->string('perguruan_tinggi');
            $table->string('akreditasi_prodi');
            $table->string('lokasi_pt');
            $table->date('rencana_mulai');
            $table->date('rencana_selesai');
            $table->enum('status', ['draft', 'pending_atasan', 'pending_admin', 'disetujui', 'ditolak', 'selesai'])->default('draft');
            $table->text('catatan_tolak')->nullable();
            $table->timestamp('tanggal_submit_atasan')->nullable();
            $table->timestamp('tanggal_approve_atasan')->nullable();
            $table->timestamp('tanggal_approve_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
};
