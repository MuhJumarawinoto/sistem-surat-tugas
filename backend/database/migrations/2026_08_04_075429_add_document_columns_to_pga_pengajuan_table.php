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
        Schema::table('pga_pengajuan', function (Blueprint $table) {
            // Dokumen 1: Surat Pengantar/Usulan dari Instansi
            $table->string('surat_pengantar_file')->nullable()->after('catatan_tolak');

            // Dokumen 2a: SK Pangkat Terakhir
            $table->string('sk_pangkat_file')->nullable()->after('surat_pengantar_file');

            // Dokumen 3: SK Jabatan Terbaru
            $table->string('sk_jabatan_file')->nullable()->after('sk_pangkat_file');

            // Dokumen 4: Surat Izin Belajar/Tugas Belajar/Surat Keterangan
            $table->string('surat_izin_file')->nullable()->after('sk_jabatan_file');

            // Dokumen 5a: Asli Ijazah (reusing ijazah_file - already exists)
            // ijazah_file already exists

            // Dokumen 5b: Lampiran Forlap Dikti dengan Keterangan LULUS
            $table->string('ijazah_forlap_file')->nullable()->after('ijazah_file');

            // Dokumen 6: Asli Transkrip Nilai (reusing transkrip_file - already exists)
            // transkrip_file already exists

            // Dokumen 7: Akreditasi Program Studi
            $table->string('akreditasi_file')->nullable()->after('transkrip_file');

            // Dokumen 8: Ijazah luar negeri yang disetarakan (Kemenristek Dikti)
            $table->string('ijazah_dikti_file')->nullable()->after('akreditasi_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pga_pengajuan', function (Blueprint $table) {
            $table->dropColumn([
                'surat_pengantar_file',
                'sk_pangkat_file',
                'sk_jabatan_file',
                'surat_izin_file',
                'ijazah_forlap_file',
                'akreditasi_file',
                'ijazah_dikti_file',
            ]);
        });
    }
};
