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
        Schema::create('verification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // Kategori: staf, kasi, kabid, kadis, sekda, bupati
            $table->string('nama_jabatan'); // Nama jabatan pemohon
            $table->string('atasan_level')->nullable(); // Level atasan yang diperlukan
            $table->string('signer_s1')->default('Kepala BKPSDM'); // Penandatangan untuk S1
            $table->string('signer_s2')->default('Sekretaris Daerah'); // Penandatangan untuk S2
            $table->string('signer_s3')->default('Bupati'); // Penandatangan untuk S3
            $table->integer('urutan')->default(0); // Urutan level (1=terendah, 10=tertinggi)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_rules');
    }
};
