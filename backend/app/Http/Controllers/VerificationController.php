<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\User;
use App\Models\VerificationRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    /**
     * Get verification info for a pengajuan
     * Returns who needs to verify and who will sign
     */
    public function getVerificationInfo(Request $request, string $pengajuanId): JsonResponse
    {
        $pengajuan = Pengajuan::with(['user', 'jenjang'])->findOrFail($pengajuanId);
        $user = $pengajuan->user;

        // Get verification rule based on user's jabatan_kategori
        $rule = null;
        if ($user->jabatan_kategori) {
            $rule = VerificationRule::getByKode($user->jabatan_kategori);
        }

        // If no rule found, try to find by jabatan name
        if (!$rule && $user->jabatan) {
            $rule = VerificationRule::findByJabatan($user->jabatan);
        }

        // Build verification chain
        $verificationChain = $this->buildVerificationChain($pengajuan, $rule);

        // Get final signer based on jenjang
        $finalSigner = $this->getFinalSigner($pengajuan, $rule);

        return response()->json([
            'pengajuan' => $pengajuan,
            'user' => $user,
            'verification_rule' => $rule,
            'verification_chain' => $verificationChain,
            'final_signer' => $finalSigner,
        ]);
    }

    /**
     * Get all verification rules (for admin)
     */
    public function getRules(Request $request): JsonResponse
    {
        $rules = VerificationRule::where('is_active', true)
            ->orderBy('urutan')
            ->get();

        return response()->json($rules);
    }

    /**
     * Get available jabatan categories (for dropdown)
     */
    public function getJabatanCategories(Request $request): JsonResponse
    {
        $categories = VerificationRule::where('is_active', true)
            ->orderBy('urutan')
            ->get(['kode', 'nama_jabatan', 'urutan']);

        return response()->json($categories);
    }

    /**
     * Build verification chain for a pengajuan
     * Direct flow: Submit -> Admin Verification -> Final Signer (no atasan approval)
     */
    private function buildVerificationChain(Pengajuan $pengajuan, ?VerificationRule $rule): array
    {
        $chain = [];
        $user = $pengajuan->user;
        $currentStatus = $pengajuan->status;

        // 1. Admin BKPSDM Verification (first step after submission)
        $chain[] = [
            'level' => 'admin_bkpsdm',
            'nama' => 'Admin BKPSDM',
            'jabatan' => 'Verifikasi Dokumen',
            'nip' => '-',
            'status' => in_array($currentStatus, ['verified', 'disetujui', 'ditolak'])
                ? 'completed'
                : (in_array($currentStatus, ['pending_admin', 'approved_admin']) ? 'current' : 'pending'),
            'urutan' => 1,
        ];

        // 2. Final Signer
        $finalSigner = $this->getFinalSigner($pengajuan, $rule);
        $chain[] = [
            'level' => 'final_signer',
            'nama' => $finalSigner['nama'],
            'jabatan' => $finalSigner['jabatan'],
            'nip' => '-',
            'status' => $currentStatus === 'disetujui' ? 'completed' : 'pending',
            'urutan' => 2,
        ];

        return $chain;
    }

    /**
     * Get final signer based on jenjang and verification rule
     */
    private function getFinalSigner(Pengajuan $pengajuan, ?VerificationRule $rule): array
    {
        $jenjang = $pengajuan->jenjang?->nama_jenjang ?? 'S1';

        if ($rule) {
            $signer = $rule->getSignerForJenjang($jenjang);

            // Map signer names to positions
            return match ($signer) {
                'Kepala BKPSDM' => [
                    'nama' => 'Kepala BKPSDM',
                    'jabatan' => 'Penandatangan Surat',
                    'level' => 'kepala_bkpsdm',
                ],
                'Sekretaris Daerah' => [
                    'nama' => 'Sekretaris Daerah',
                    'jabatan' => 'Penandatangan Surat',
                    'level' => 'sekda',
                ],
                'Bupati' => [
                    'nama' => 'Bupati',
                    'jabatan' => 'Penandatangan Surat',
                    'level' => 'bupati',
                ],
                default => [
                    'nama' => 'Kepala BKPSDM',
                    'jabatan' => 'Penandatangan Surat',
                    'level' => 'kepala_bkpsdm',
                ],
            };
        }

        // Default fallback
        return [
            'nama' => 'Kepala BKPSDM',
            'jabatan' => 'Penandatangan Surat',
            'level' => 'kepala_bkpsdm',
        ];
    }

    /**
     * Get human-readable label for level
     */
    private function getLevelLabel(string $level): string
    {
        return match ($level) {
            'kasi' => 'Kepala Seksi / Kasubbag',
            'kabid' => 'Kepala Bidang',
            'kadis' => 'Kepala Dinas / Kepala Badan',
            'sekda' => 'Sekretaris Daerah',
            'bupati' => 'Bupati',
            'kepala_bkpsdm' => 'Kepala BKPSDM',
            default => ucfirst(str_replace('_', ' ', $level)),
        };
    }
}
