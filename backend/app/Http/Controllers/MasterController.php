<?php

namespace App\Http\Controllers;

use App\Models\JenjangPendidikan;
use App\Models\UnitKerja;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function jenjang()
    {
        $jenjang = JenjangPendidikan::where('is_active', true)
            ->orderBy('urutan')
            ->get();

        return response()->json($jenjang);
    }

    public function unitKerja()
    {
        $unitKerja = UnitKerja::where('is_active', true)
            ->orderBy('nama')
            ->get();

        return response()->json($unitKerja);
    }

    public function statusPengajuan()
    {
        $status = [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'pending_atasan', 'label' => 'Pending Atasan'],
            ['value' => 'pending_admin', 'label' => 'Pending Admin'],
            ['value' => 'disetujui', 'label' => 'Disetujui'],
            ['value' => 'ditolak', 'label' => 'Ditolak'],
            ['value' => 'selesai', 'label' => 'Selesai'],
        ];

        return response()->json($status);
    }

    public function jenisDokumen()
    {
        $jenisDokumen = [
            ['value' => 'sk_pangkat', 'label' => 'SK Pangkat Terakhir', 'required' => true],
            ['value' => 'sk_cpns', 'label' => 'SK CPNS', 'required' => true],
            ['value' => 'skp', 'label' => 'SKP 2 Tahun Terakhir', 'required' => true],
            ['value' => 'surat_lulus', 'label' => 'Surat Keterangan Lulus/Diterima', 'required' => true],
            ['value' => 'jadwal', 'label' => 'Jadwal Perkuliahan', 'required' => true],
            ['value' => 'akreditasi', 'label' => 'Sertifikat Akreditasi Prodi', 'required' => true],
            ['value' => 'surat_mandiri', 'label' => 'Surat Pernyataan Biaya Mandiri', 'required' => true],
            ['value' => 'surat_ijazah', 'label' => 'Surat Pernyataan Tidak Menuntut Ijazah', 'required' => true],
            ['value' => 'surat_sehat', 'label' => 'Surat Keterangan Sehat', 'required' => true],
        ];

        return response()->json($jenisDokumen);
    }

    public function akreditasi()
    {
        $akreditasi = [
            ['value' => 'A', 'label' => 'A (Sangat Baik)'],
            ['value' => 'B', 'label' => 'B (Baik)'],
            ['value' => 'C', 'label' => 'C (Cukup)'],
            ['value' => 'Unggul', 'label' => 'Unggul'],
        ];

        return response()->json($akreditasi);
    }
}
