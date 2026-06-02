<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratIzinBelajar extends Model
{
    use HasFactory;

    protected $table = 'surat_izin_belajar';

    protected $fillable = [
        'pengajuan_id',
        'surat_tugas_dinas_id',
        'nomor_surat',
        'tahun',
        'file_path',
        'tte_path',
        'qr_code',
        'status',
        'signed_at',
        'signed_by',
        'signed_by_nip',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * Get the pengajuan that owns the surat izin belajar.
     */
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Get the surat tugas dinas that this surat izin belongs to.
     */
    public function suratTugasDinas()
    {
        return $this->belongsTo(SuratTugasDinas::class);
    }

    /**
     * Scope to get only surat with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending surat (draft status).
     */
    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get signed surat.
     */
    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    /**
     * Check if surat can be signed.
     */
    public function canBeSigned(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if surat has been signed.
     */
    public function isSigned(): bool
    {
        return in_array($this->status, ['signed', 'completed']) && $this->signed_at !== null;
    }

    /**
     * Mark surat as signed.
     */
    public function markAsSigned(string $signedBy, ?string $signedByNip = null): void
    {
        $this->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => $signedBy,
            'signed_by_nip' => $signedByNip,
        ]);
    }

    /**
     * Mark surat as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
        ]);
    }
}
