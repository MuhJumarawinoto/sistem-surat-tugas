<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pengajuan_id',
    'jenis_dokumen',
    'file_path',
    'file_name',
    'file_type',
    'file_size',
    'status_verifikasi',
    'catatan',
    'verified_by',
    'verified_at',
])]
class DokumenPengajuan extends Model
{
    protected $table = 'dokumen_pengajuan';

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Relasi ke JenisDokumen berdasarkan kode
     */
    public function jenisDokumenRelasi(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen', 'kode');
    }

    /**
     * Get label jenis dokumen dari relasi atau fallback ke hardcoded
     */
    public function getJenisDokumenLabelAttribute(): string
    {
        // Gunakan relasi jika ada
        if ($this->jenisDokumenRelasi) {
            return $this->jenisDokumenRelasi->nama;
        }

        // Fallback untuk data lama atau jika relasi tidak ditemukan
        return match ($this->jenis_dokumen) {
            'sk_pangkat' => 'SK Pangkat Terakhir',
            'sk_cpns' => 'SK CPNS',
            'skp' => 'SKP 2 Tahun Terakhir',
            'surat_lulus' => 'Surat Keterangan Lulus/Diterima',
            'jadwal' => 'Jadwal Perkuliahan',
            'akreditasi' => 'Sertifikat Akreditasi Prodi',
            'surat_mandiri' => 'Surat Pernyataan Biaya Mandiri',
            'surat_ijazah' => 'Surat Pernyataan Tidak Menuntut Ijazah',
            'surat_sehat' => 'Surat Keterangan Sehat',
            default => 'Dokumen Lainnya',
        };
    }

    public function getFileSizeInMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}
