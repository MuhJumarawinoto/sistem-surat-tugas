<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\SuratTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;

class SuratTugasController extends Controller
{
    public function generate(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with(['user', 'jenjang', 'unitKerja'])->findOrFail($id);

        if (!$pengajuan->isDisetujui()) {
            return response()->json(['message' => 'Pengajuan must be approved first'], 400);
        }

        $existing = $pengajuan->suratTugas()->first();

        if ($existing) {
            return response()->json($existing);
        }

        $nomorSurat = $this->generateNomorSurat();

        $pdf = $this->generatePdf($pengajuan, $nomorSurat);

        $fileName = 'surat-tugas-' . $pengajuan->nomor_pengajuan . '.pdf';
        $path = 'surat-tugas/' . $fileName;

        Storage::disk('public')->put($path, $pdf->output());

        $surat = SuratTugas::create([
            'pengajuan_id' => $id,
            'nomor_surat' => $nomorSurat,
            'tanggal_terbit' => now(),
            'file_path' => $path,
            'status_tte' => 'pending',
        ]);

        return response()->json($surat->load('pengajuan'));
    }

    public function show(string $id)
    {
        $surat = SuratTugas::with(['pengajuan.user', 'pengajuan.jenjang', 'signedBy'])->findOrFail($id);

        return response()->json($surat);
    }

    public function download(string $id)
    {
        $surat = SuratTugas::findOrFail($id);

        $path = storage_path('app/public/' . $surat->file_path);

        if (!file_exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($path, 'surat-tugas.pdf');
    }

    public function signTte(Request $request, string $id)
    {
        $surat = SuratTugas::findOrFail($id);

        if ($surat->isSigned()) {
            return response()->json(['message' => 'Surat already signed'], 400);
        }

        $surat->update([
            'status_tte' => 'signed',
            'signed_by' => $request->user()->id,
            'signed_at' => now(),
            'tte_qr_code' => 'QR-' . $surat->nomor_surat,
        ]);

        return response()->json($surat->load(['pengajuan', 'signedBy']));
    }

    private function generateNomorSurat(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastNomor = SuratTugas::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = $lastNomor ? (int) explode('/', $lastNomor->nomor_surat)[2] + 1 : 1;

        return '800.1.3.1/' . $sequence . '/BKPSDM/' . $year;
    }

    private function generatePdf(Pengajuan $pengajuan, string $nomorSurat)
    {
        $data = [
            'nomor_surat' => $nomorSurat,
            'pengajuan' => $pengajuan,
            'tanggal_terbit' => now()->format('d F Y'),
        ];

        return PDF::loadView('pdf.surat-tugas', $data);
    }
}
