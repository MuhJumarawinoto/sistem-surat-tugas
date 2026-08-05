<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumenPga;
use App\Models\Notification;
use App\Models\PgaPengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PgaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PgaPengajuan::with([
            'user:id,name,nip,jabatan,unit_kerja_id',
            'jenjangPendidikan:id,nama,kode,urutan',
        ]);

        // Include deleted (soft deleted) if requested
        $includeDeleted = $request->has('include_deleted') && $request->input('include_deleted') === '1';
        if (! $includeDeleted) {
            $query->whereNull('deleted_at');
        }

        if ($user->isPemohon() || $user->isAtasan()) {
            // Pemohon/Atasan: hanya melihat pengajuan sendiri
            $query->where('user_id', $user->id);
        } elseif ($user->isKepalaUnit()) {
            // Kepala Unit: melihat pengajuan pegawai di unit kerja + pengajuan sendiri
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('user', function ($subQuery) use ($user) {
                        $subQuery->where('unit_kerja_id', $user->unit_kerja_id);
                    });
            });
        } elseif ($user->isAdminBkpsdm() || $user->isKepalaBkpsdm()) {
            // Admin/Kepala BKPSDM: melihat semua pengajuan
            // Order by: approved_admin, draft, selesai, ditolak
            if (! $request->has('status')) {
                $query->orderByRaw("
                    CASE status
                        WHEN 'approved_admin' THEN 1
                        WHEN 'draft' THEN 2
                        WHEN 'selesai' THEN 3
                        WHEN 'ditolak' THEN 4
                        ELSE 5
                    END
                ");
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $pga = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($pga);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Get active document types for dynamic validation
        $activeDocTypes = JenisDokumenPga::active()->get();
        $docValidationRules = [];
        foreach ($activeDocTypes as $docType) {
            $docValidationRules[$docType->kode] = 'nullable|file|mimes:pdf|max:1024';
        }

        $request->validate(array_merge([
            'jenjang_pendidikan_id' => 'required|exists:jenjang_pendidikan,id',
            'nama_prodi' => 'required|string|max:255',
            'perguruan_tinggi' => 'required|string|max:255',
            'lokasi_pt' => 'nullable|string|max:255',
            'nomor_ijazah' => 'nullable|string|max:100',
            'tanggal_ijazah' => 'nullable|date',
            'tahun_lulus' => 'required|integer|min:1970|max:'.(date('Y') + 1),
            'gelar_akademik' => 'nullable|string|max:50',
        ], $docValidationRules));

        // Generate nomor pengajuan
        $year = date('Y');
        $month = date('m');
        $count = PgaPengajuan::withTrashed()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        $nomorPengajuan = 'PGA-'.$year.$month.str_pad($count, 4, '0', STR_PAD_LEFT);

        // Handle file uploads dynamically
        $filePaths = [];
        foreach ($activeDocTypes as $docType) {
            $filePaths[$docType->kode] = null;
            if ($request->hasFile($docType->kode)) {
                $storagePath = 'pga/'.str_replace('_file', '', $docType->kode);
                $filePaths[$docType->kode] = $request->file($docType->kode)->store($storagePath, 'public');
            }
        }

        $pga = PgaPengajuan::create([
            'nomor_pengajuan' => $nomorPengajuan,
            'user_id' => $user->id,
            'jenjang_pendidikan_id' => $request->jenjang_pendidikan_id,
            'nama_prodi' => $request->nama_prodi,
            'perguruan_tinggi' => $request->perguruan_tinggi,
            'lokasi_pt' => $request->lokasi_pt,
            'nomor_ijazah' => $request->nomor_ijazah,
            'tanggal_ijazah' => $request->tanggal_ijazah,
            'tahun_lulus' => $request->tahun_lulus,
            'gelar_akademik' => $request->gelar_akademik,
            'status' => 'draft',
        ] + $filePaths);

        return response()->json([
            'data' => $pga->load(['user', 'jenjangPendidikan']),
            'message' => 'Pengajuan PGA berhasil dibuat',
        ], 201);
    }

    public function show(string $id)
    {
        $pga = PgaPengajuan::with(['user.unitKerja', 'jenjangPendidikan'])
            ->withTrashed()
            ->findOrFail($id);

        $user = request()->user();

        // Admin can view all
        if ($user->isAdminBkpsdm() || $user->isKepalaBkpsdm()) {
            return response()->json($pga);
        }

        // Kepala unit can view own unit
        if ($user->isKepalaUnit()) {
            $canView = $pga->user_id === $user->id
                || $pga->user->unit_kerja_id === $user->unit_kerja_id;

            if (! $canView) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json($pga);
        }

        // Pemohon/Atasan: hanya bisa lihat pengajuan sendiri
        if ($pga->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($pga);
    }

    public function update(Request $request, string $id)
    {
        $pga = PgaPengajuan::findOrFail($id);
        $user = $request->user();

        // Authorization: only owner or admin can edit
        if ($user->isPemohon() || $user->isAtasan()) {
            if ($pga->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only edit draft or rejected
        if (! $pga->isDraft() && ! $pga->isDitolak()) {
            return response()->json(['message' => 'Hanya dapat mengedit pengajuan draft atau ditolak'], 400);
        }

        // Get active document types for dynamic validation
        $activeDocTypes = JenisDokumenPga::active()->get();
        $docValidationRules = [];
        foreach ($activeDocTypes as $docType) {
            $docValidationRules[$docType->kode] = 'nullable|file|mimes:pdf|max:1024';
        }

        $request->validate(array_merge([
            'jenjang_pendidikan_id' => 'required|exists:jenjang_pendidikan,id',
            'nama_prodi' => 'required|string|max:255',
            'perguruan_tinggi' => 'required|string|max:255',
            'lokasi_pt' => 'nullable|string|max:255',
            'nomor_ijazah' => 'nullable|string|max:100',
            'tanggal_ijazah' => 'nullable|date',
            'tahun_lulus' => 'required|integer|min:1970|max:'.(date('Y') + 1),
            'gelar_akademik' => 'nullable|string|max:50',
        ], $docValidationRules));

        // Handle file uploads - dynamic approach for all document types
        $updateData = [
            'jenjang_pendidikan_id' => $request->jenjang_pendidikan_id,
            'nama_prodi' => $request->nama_prodi,
            'perguruan_tinggi' => $request->perguruan_tinggi,
            'lokasi_pt' => $request->lokasi_pt,
            'nomor_ijazah' => $request->nomor_ijazah,
            'tanggal_ijazah' => $request->tanggal_ijazah,
            'tahun_lulus' => $request->tahun_lulus,
            'gelar_akademik' => $request->gelar_akademik,
        ];

        foreach ($activeDocTypes as $docType) {
            if ($request->hasFile($docType->kode)) {
                // Delete old file if exists
                if ($pga->{$docType->kode}) {
                    Storage::disk('public')->delete($pga->{$docType->kode});
                }
                $storagePath = 'pga/'.str_replace('_file', '', $docType->kode);
                $updateData[$docType->kode] = $request->file($docType->kode)->store($storagePath, 'public');
            }
        }

        // Reset status to draft if was rejected
        if ($pga->isDitolak()) {
            $updateData['status'] = 'draft';
            $updateData['catatan_tolak'] = null;
        }

        $pga->update($updateData);

        return response()->json($pga->load(['user', 'jenjangPendidikan']));
    }

    public function destroy(string $id)
    {
        $pga = PgaPengajuan::findOrFail($id);
        $user = request()->user();

        // Authorization
        if ($user->isPemohon() || $user->isAtasan()) {
            if ($pga->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only delete draft
        if (! $pga->isDraft()) {
            return response()->json(['message' => 'Hanya dapat menghapus pengajuan draft'], 400);
        }

        // Soft delete
        $pga->delete();

        return response()->json(['message' => 'Pengajuan berhasil dihapus']);
    }

    public function submit(Request $request, string $id)
    {
        $pga = PgaPengajuan::findOrFail($id);
        $user = $request->user();

        // Authorization: only owner can submit
        if ($pga->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only submit draft
        if (! $pga->isDraft()) {
            return response()->json(['message' => 'Hanya dapat mengirim pengajuan draft'], 400);
        }

        // Validate all required documents are uploaded
        $requiredDocTypes = JenisDokumenPga::required()->get();
        foreach ($requiredDocTypes as $docType) {
            if (empty($pga->{$docType->kode})) {
                return response()->json(['message' => "Harap upload semua dokumen wajib. Dokumen {$docType->nama} belum diupload."], 400);
            }
        }

        $pga->update([
            'status' => 'approved_admin',
            'tanggal_approve_admin' => now(),
        ]);

        // Notify all admin BKPSDM
        $adminUsers = User::whereHas('role', function ($query) {
            $query->where('slug', 'admin_bkpsdm');
        })->get();

        foreach ($adminUsers as $admin) {
            Notification::createForUser(
                userId: $admin->id,
                type: 'pga_baru',
                title: 'Pengajuan PGA Baru',
                message: "Pengajuan Pencantuman Gelar Akademik dari {$user->name} menunggu verifikasi.",
                pgaId: $pga->id
            );
        }

        return response()->json([
            'data' => $pga->load(['user', 'jenjangPendidikan']),
            'message' => 'Pengajuan berhasil dikirim',
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $pga = PgaPengajuan::findOrFail($id);
        $user = $request->user();

        // Only admin can approve
        if (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only approve approved_admin status
        if (! $pga->isApprovedAdmin()) {
            return response()->json(['message' => 'Pengajuan tidak dalam status menunggu persetujuan'], 400);
        }

        $pga->update([
            'status' => 'selesai',
            'tanggal_selesai' => now(),
        ]);

        // Notify user
        Notification::createForUser(
            userId: $pga->user_id,
            type: 'pga_disetujui',
            title: 'PGA Disetujui',
            message: 'Pengajuan Pencantuman Gelar Akademik Anda telah disetujui.',
            pgaId: $pga->id
        );

        return response()->json([
            'data' => $pga->load(['user', 'jenjangPendidikan']),
            'message' => 'Pengajuan PGA berhasil disetujui',
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'catatan_tolak' => 'required|string|max:500',
        ]);

        $pga = PgaPengajuan::findOrFail($id);
        $user = $request->user();

        // Only admin can reject
        if (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only reject approved_admin status
        if (! $pga->isApprovedAdmin()) {
            return response()->json(['message' => 'Pengajuan tidak dalam status menunggu persetujuan'], 400);
        }

        $pga->update([
            'status' => 'ditolak',
            'catatan_tolak' => $request->catatan_tolak,
        ]);

        // Notify user
        Notification::createForUser(
            userId: $pga->user_id,
            type: 'pga_ditolak',
            title: 'PGA Ditolak',
            message: 'Pengajuan Pencantuman Gelar Akademik Anda ditolak. '.$request->catatan_tolak,
            pgaId: $pga->id
        );

        return response()->json([
            'data' => $pga->load(['user', 'jenjangPendidikan']),
            'message' => 'Pengajuan PGA ditolak',
        ]);
    }

    public function restore(string $id)
    {
        $pga = PgaPengajuan::withTrashed()->findOrFail($id);
        $user = request()->user();

        // Authorization
        if ($user->isPemohon() || $user->isAtasan()) {
            if ($pga->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Restore soft deleted
        if (! $pga->trashed()) {
            return response()->json(['message' => 'Pengajuan tidak dalam status dihapus'], 400);
        }

        $pga->restore();

        return response()->json([
            'data' => $pga->load(['user', 'jenjangPendidikan']),
            'message' => 'Pengajuan berhasil dipulihkan',
        ]);
    }

    public function downloadDocument(string $id, string $type)
    {
        $pga = PgaPengajuan::findOrFail($id);
        $user = request()->user();

        // Authorization check
        if ($user->isPemohon() || $user->isAtasan()) {
            if ($pga->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (! $user->isAdminBkpsdm() && ! $user->isKepalaBkpsdm() && ! $user->isKepalaUnit()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filePath = match ($type) {
            'surat_pengantar' => $pga->surat_pengantar_file,
            'sk_pangkat' => $pga->sk_pangkat_file,
            'sk_jabatan' => $pga->sk_jabatan_file,
            'surat_izin' => $pga->surat_izin_file,
            'ijazah' => $pga->ijazah_file,
            'ijazah_forlap' => $pga->ijazah_forlap_file,
            'transkrip' => $pga->transkrip_file,
            'akreditasi' => $pga->akreditasi_file,
            'ijazah_dikti' => $pga->ijazah_dikti_file,
            'sk_kum' => $pga->sk_kum_file, // Legacy support
            default => null,
        };

        if (! $filePath) {
            return response()->json(['message' => 'Dokumen tidak ditemukan'], 404);
        }

        $fullPath = storage_path('app/public/'.$filePath);

        if (! file_exists($fullPath)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return response()->download($fullPath, basename($filePath));
    }
}
