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

    public function suratTugasDinas(): HasMany
    {
        return $this->hasMany(SuratTugasDinas::class);
    }

    public function suratIzinBelajar(): HasMany
    {
        return $this->hasMany(SuratIzinBelajar::class);
    }

    public function latestSuratTugasDinas(): BelongsTo
    {
        return $this->belongsTo(SuratTugasDinas::class);
    }

    public function latestSuratIzinBelajar(): BelongsTo
    {
        return $this->belongsTo(SuratIzinBelajar::class);
    }

    public function suratTugasMandiri(): HasMany
    {
        return $this->hasMany(SuratTugasMandiri::class);
    }

    public function latestSuratTugasMandiri(): BelongsTo
    {
        return $this->belongsTo(SuratTugasMandiri::class);
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

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isSuratDinas(): bool
    {
        return $this->status === 'surat_dinas';
    }

    public function isSuratIzin(): bool
    {
        return $this->status === 'surat_izin';
    }

    public function hasSuratTugasDinas(): bool
    {
        return $this->suratTugasDinas()->exists();
    }

    public function hasSuratIzinBelajar(): bool
    {
        return $this->suratIzinBelajar()->exists();
    }

    public function needsSuratTugasDinas(): bool
    {
        return $this->isVerified() && !$this->hasSuratTugasDinas();
    }

    public function needsSuratIzinBelajar(): bool
    {
        return $this->hasSuratTugasDinas() && !$this->hasSuratIzinBelajar();
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
