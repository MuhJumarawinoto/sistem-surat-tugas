<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratTugasMandiri extends Model
{
    protected $table = 'surat_tugas_mandiri';

    protected $fillable = [
        'pengajuan_id',
        'surat_izin_belajar_id',
        'surat_tugas_dinas_id',
        'nomor_surat',
        'tahun',
        'tanggal_surat',
        'tempat_ttd',
        'file_path',
        'tte_path',
        'qr_code',
        'status',
        'signed_at',
        'signed_by',
        'signed_by_nip',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'signed_at' => 'datetime',
        'qr_code' => 'array',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function suratIzinBelajar()
    {
        return $this->belongsTo(SuratIzinBelajar::class);
    }

    public function suratTugasDinas()
    {
        return $this->belongsTo(SuratTugasDinas::class);
    }

    public function canBeSigned()
    {
        return $this->status === 'draft';
    }

    public function isSigned()
    {
        return $this->status === 'signed';
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByPengajuan($query, $pengajuanId)
    {
        return $query->where('pengajuan_id', $pengajuanId);
    }
}
