<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFEditorController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is logged in via session
        if (!auth()->check()) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $user = auth()->user();

        // Only admin can access
        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return redirect('/dashboard')->with('error', 'Akses ditolak');
        }

        // Get API token for the user
        $token = $user->tokens->first()->token ?? '';

        return view('pdf-editor', [
            'user' => $user,
            'apiToken' => $token,
            'apiUrl' => config('app.url') . '/api',
        ]);
    }

    public function preview(Request $request)
    {
        // Check if user is logged in
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Only admin can access
        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Create dummy surat object for preview
        $surat = new \stdClass();
        $surat->id = 0;
        $surat->nomor_surat = $request->nomor_surat ?? '800.1.3.1/001/BKPSDM/' . date('Y');
        $surat->tahun = $request->tahun ?? date('Y');
        $surat->tanggal_surat = $request->tanggal_surat ?? date('d F Y');
        $surat->tempat_ttd = $request->tempat_ttd ?? 'Sukabumi';
        $surat->status = 'preview';

        // Create dummy pengajuan data
        $surat->pengajuan = new \stdClass();
        $surat->pengajuan->user = new \stdClass();
        $surat->pengajuan->user->name = $request->nama ?? 'Nama Pegawai';
        $surat->pengajuan->user->nip = $request->nip ?? '198001012010011001';
        $surat->pengajuan->user->pangkat_gol = $request->pangkat ?? 'Pembina (IV/a)';
        $surat->pengajuan->user->jabatan = $request->jabatan ?? 'Jabatan Pegawai';
        $surat->pengajuan->user->unitKerja = new \stdClass();
        $surat->pengajuan->user->unitKerja->nama = $request->unit_kerja ?? 'Dinas Pemerintahan';

        $surat->pengajuan->jenjang = new \stdClass();
        $surat->pengajuan->jenjang->nama = $request->jenjang ?? 'Magister (S2)';

        $surat->pengajuan->nama_prodi = $request->nama_prodi ?? 'Magister Administrasi Publik';
        $surat->pengajuan->perguruan_tinggi = $request->perguruan_tinggi ?? 'Universitas Indonesia';
        $surat->pengajuan->lokasi_pt = $request->lokasi_pt ?? 'Depok, Jawa Barat';

        $surat->suratTugasDinas = new \stdClass();
        $surat->suratTugasDinas->nomor_surat = $request->nomor_surat_dinas ?? '001/DK/Mei/' . date('Y');
        $surat->suratTugasDinas->tanggal_mulai = $request->tanggal_mulai ?? date('Y-m-d');
        $surat->suratTugasDinas->tanggal_selesai = $request->tanggal_selesai ?? date('Y-m-d', strtotime('+2 years'));
        $surat->suratTugasDinas->unitKerja = new \stdClass();
        $surat->suratTugasDinas->unitKerja->nama = $request->dinas ?? 'Dinas Pendidikan';
        $surat->suratTugasDinas->kepalaDinas = new \stdClass();
        $surat->suratTugasDinas->kepalaDinas->nama = $request->nama_kepala_dinas ?? 'Nama Kepala Dinas';
        $surat->suratTugasDinas->kepalaDinas->nip = $request->nip_kepala_dinas ?? '197001011995031001';

        // Get kepala BKPSDM data
        $kepalaBkpsdm = \App\Models\User::where('role_id', 4)->first();
        if ($kepalaBkpsdm) {
            $surat->kepala_bkpsdm = $kepalaBkpsdm;
        }

        return view('pdf.surat-izin-belajar', [
            'surat' => $surat,
            'preview' => true,
        ]);
    }

    public function generatePdf(Request $request)
    {
        // Check if user is logged in
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Only admin can access
        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Reuse preview logic to get HTML
        $response = $this->preview($request);
        $html = $response->getContent();

        $pdf = Pdf::loadHTML($html);
        $filename = "Preview_Surat_Izin_Belajar_" . date('YmdHis') . ".pdf";

        return $pdf->stream($filename);
    }
}
