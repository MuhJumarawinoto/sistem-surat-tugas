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
        Schema::create('pga_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengajuan')->unique();

            // User relation
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Education data
            $table->foreignId('jenjang_pendidikan_id')->constrained('jenjang_pendidikan')->restrictOnDelete();
            $table->string('gelar_akademik')->nullable();
            $table->string('nama_prodi');
            $table->string('perguruan_tinggi');
            $table->string('lokasi_pt')->nullable();
            $table->string('nomor_ijazah')->nullable();
            $table->date('tanggal_ijazah')->nullable();
            $table->year('tahun_lulus');

            // Status flow: draft → approved_admin → selesai / ditolak
            $table->enum('status', ['draft', 'approved_admin', 'selesai', 'ditolak'])->default('draft');
            $table->text('catatan_tolak')->nullable();

            // Approval timestamps
            $table->timestamp('tanggal_approve_admin')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();

            // File documents
            $table->string('ijazah_file')->nullable();
            $table->string('transkrip_file')->nullable();
            $table->string('sk_kum_file')->nullable(); // SK Kenaikan Pangkat/Golongan Terakhir

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pga_pengajuan');
    }
};
