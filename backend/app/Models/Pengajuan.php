<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nomor_pengajuan',
    'user_id',
    'jenjang_id',
    'nama_prodi',
    'perguruan_tinggi',
    'akreditasi_prodi',
    'lokasi_pt',
    'rencana_mulai',
    'rencana_selesai',
    'status',
    'catatan_tolak',
    'approval_level',
    'approved_by_atasan',
    'approved_at_atasan',
    'tanggal_submit_atasan',
    'tanggal_approve_atasan',
    'tanggal_approve_admin',
])]
class Pengajuan extends Model
{
    protected $table = 'pengajuan';

    protected function casts(): array
    {
        return [
            'rencana_mulai' => 'date',
            'rencana_selesai' => 'date',
            'approved_at_atasan' => 'datetime',
            'tanggal_submit_atasan' => 'datetime',
            'tanggal_approve_atasan' => 'datetime',
            'tanggal_approve_admin' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByAtasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_atasan');
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class);
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function suratTugas(): HasMany
    {
        return $this->hasMany(SuratTugas::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingAtasan(): bool
    {
        return $this->status === 'pending_atasan';
    }

    public function isPendingAdmin(): bool
    {
        return $this->status === 'pending_admin';
    }

    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function isAtasanApplicant(): bool
    {
        return $this->approval_level === 'atasan';
    }

    public function requiresSpecialApproval(): bool
    {
        return $this->approval_level === 'atasan';
    }

    public function getAllDocumentsUploaded(): bool
    {
        $requiredDocuments = [
            'sk_pangkat',
            'sk_cpns',
            'skp',
            'surat_lulus',
            'jadwal',
            'akreditasi',
            'surat_mandiri',
            'surat_ijazah',
            'surat_sehat',
        ];

        $uploadedTypes = $this->dokumen->pluck('jenis_dokumen')->toArray();

        return empty(array_diff($requiredDocuments, $uploadedTypes));
    }
}
