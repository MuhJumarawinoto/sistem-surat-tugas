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
        Schema::create('jenis_dokumen_pga', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique()->comment('Kode dokumen untuk field database (contoh: surat_pengantar_file)');
            $table->string('nama')->comment('Nama dokumen yang ditampilkan ke user');
            $table->text('deskripsi')->nullable()->comment('Deskripsi dokumen');
            $table->boolean('is_wajib')->default(true)->comment('Apakah dokumen wajib diupload');
            $table->integer('urutan')->default(0)->comment('Urutan tampilan');
            $table->json('persyaratan')->nullable()->comment('Persyaratan dokumen dalam format JSON');
            $table->string('format_nama')->nullable()->comment('Format penamaan file (contoh: PENGANTAR_PG_NIP)');
            $table->text('catatan')->nullable()->comment('Catatan tambahan untuk user');
            $table->boolean('is_active')->default(true)->comment('Apakah dokumen aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_dokumen_pga');
    }
};
