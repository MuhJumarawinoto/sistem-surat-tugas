<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratTugasDinas extends Model
{
    use HasFactory;

    protected $fillable = [
        'pengajuan_id',
        'unit_kerja_id',
        'kepala_dinas_id',
        'nomor_surat',
        'bulan',
        'tahun',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_ttd',
        'tempat_ttd',
        'file_path',
        'status',
        'signed_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_ttd' => 'date',
        'signed_at' => 'datetime',
    ];

    /**
     * Get the pengajuan that owns the surat tugas dinas.
     */
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Get the unit kerja that owns the surat tugas dinas.
     */
    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }

    /**
     * Get the kepala dinas (user) that created the surat.
     */
    public function kepalaDinas()
    {
        return $this->belongsTo(User::class, 'kepala_dinas_id');
    }

    /**
     * Get the surat izin belajar for this surat tugas dinas.
     */
    public function suratIzinBelajar()
    {
        return $this->hasOne(SuratIzinBelajar::class);
    }

    /**
     * Scope to get only surat tugas dinas for a specific unit kerja.
     */
    public function scopeForUnitKerja($query, $unitKerjaId)
    {
        return $query->where('unit_kerja_id', $unitKerjaId);
    }

    /**
     * Scope to get only surat with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get full nomor surat format.
     */
    public function getFullNomorSuratAttribute(): string
    {
        return "{$this->nomor_surat}/DK/{$this->bulan}/{$this->tahun}";
    }

    /**
     * Check if surat can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if surat can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->status === 'draft';
    }
}
