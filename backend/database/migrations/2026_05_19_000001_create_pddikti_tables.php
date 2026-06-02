<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perguruan_tinggi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pt', 50)->unique();
            $table->string('nama_pt');
            $table->string('nama_singkat')->nullable();
            $table->string('jenis_perguruan_tinggi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kab_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('website')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('akreditasi')->nullable();
            $table->string('status_pt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('nama_pt');
            $table->index('kode_pt');
        });

        Schema::create('prodis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perguruan_tinggi_id')->nullable()->constrained('perguruan_tinggi')->nullOnDelete();
            $table->string('kode_prodi', 50)->nullable();
            $table->string('nama_prodi');
            $table->string('jenjang')->nullable();
            $table->string('akreditasi')->nullable();
            $table->string('akreditasi_internasional')->nullable();
            $table->string('status_prodi')->nullable();
            $table->string('bidang_ilmu')->nullable();
            $table->string('id_prodi_external')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('nama_prodi');
            $table->index('kode_prodi');
            $table->index('perguruan_tinggi_id');
            $table->index('jenjang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodis');
        Schema::dropIfExists('perguruan_tinggi');
    }
};
