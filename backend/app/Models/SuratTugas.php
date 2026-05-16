<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pengajuan_id',
    'nomor_surat',
    'tanggal_terbit',
    'file_path',
    'status_tte',
    'tte_qr_code',
    'signed_by',
    'signed_at',
])]
class SuratTugas extends Model
{
    protected $table = 'surat_tugas';

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function isSigned(): bool
    {
        return $this->status_tte === 'signed';
    }

    public function isPending(): bool
    {
        return $this->status_tte === 'pending';
    }
}
