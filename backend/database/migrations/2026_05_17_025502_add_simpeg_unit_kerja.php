<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Unit kerja dari SIMPEG BKPSDM
        $unitKerjas = [
            ['kode' => 'SEKRETARIAT', 'nama' => 'Sekretariat Badan Kepegawaian dan Pengembangan SDM', 'singkatan' => 'Set BKPSDM'],
            ['kode' => 'SUB_BAG_UMUM', 'nama' => 'Sub Bagian Umum dan Kepegawaian', 'singkatan' => 'Sub Bag Umum'],
            ['kode' => 'SUB_BAG_KEUANGAN', 'nama' => 'Sub Bagian Keuangan', 'singkatan' => 'Sub Bag Keuangan'],
            ['kode' => 'BIDANG_PPP', 'nama' => 'Bidang Pengadaan, Pemberhentian dan Informasi ASN', 'singkatan' => 'Bidang PPP'],
            ['kode' => 'BIDANG_KDH', 'nama' => 'Bidang Kinerja, Disiplin dan Penghargaan ASN', 'singkatan' => 'Bidang KDH'],
            ['kode' => 'BIDANG_PSDM', 'nama' => 'Bidang Pengembangan Sumber Daya Manusia', 'singkatan' => 'Bidang PSDM'],
            ['kode' => 'BIDANG_MKP', 'nama' => 'Bidang Mutasi, Kepangkatan dan Promosi ASN', 'singkatan' => 'Bidang MKP'],
            ['kode' => 'SUBKOR_KINERJA', 'nama' => 'Sub Koordinator Kinerja', 'singkatan' => 'Subkor Kinerja'],
            ['kode' => 'SUBKOR_KOMPETENSI', 'nama' => 'Sub Koordinator Pengembangan Kompetensi Teknis dan Fungsional', 'singkatan' => 'Subkor Kompetensi'],
            ['kode' => 'SUBKOR_PENGHARGAAN', 'nama' => 'Sub Koordinator Penghargaan', 'singkatan' => 'Subkor Penghargaan'],
            ['kode' => 'SUBKOR_MUTASI', 'nama' => 'Sub Koordinator Mutasi', 'singkatan' => 'Subkor Mutasi'],
            ['kode' => 'SUBKOR_PERENCANAAN', 'nama' => 'Sub Koordinator Perencanaan dan Evaluasi', 'singkatan' => 'Subkor Perencanaan'],
        ];

        foreach ($unitKerjas as $unit) {
            DB::table('unit_kerja')->updateOrInsert(
                ['kode' => $unit['kode']],
                [
                    'nama' => $unit['nama'],
                    'singkatan' => $unit['singkatan'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $kodeUnitKerjas = [
            'SEKRETARIAT',
            'SUB_BAG_UMUM',
            'SUB_BAG_KEUANGAN',
            'BIDANG_PPP',
            'BIDANG_KDH',
            'BIDANG_PSDM',
            'BIDANG_MKP',
            'SUBKOR_KINERJA',
            'SUBKOR_KOMPETENSI',
            'SUBKOR_PENGHARGAAN',
            'SUBKOR_MUTASI',
            'SUBKOR_PERENCANAAN',
        ];

        DB::table('unit_kerja')->whereIn('kode', $kodeUnitKerjas)->delete();
    }
};
