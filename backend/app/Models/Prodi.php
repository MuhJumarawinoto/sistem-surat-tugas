<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prodi extends Model
{
    protected $table = 'prodis';

    protected $fillable = [
        'perguruan_tinggi_id',
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'akreditasi',
        'akreditasi_internasional',
        'status_prodi',
        'bidang_ilmu',
        'id_prodi_external',
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    public function perguruanTinggi(): BelongsTo
    {
        return $this->belongsTo(PerguruanTinggi::class);
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('nama_prodi', 'like', "%{$keyword}%")
            ->orWhere('kode_prodi', 'like', "%{$keyword}%");
    }

    public function scopeByPerguruanTinggi($query, $ptId)
    {
        return $query->where('perguruan_tinggi_id', $ptId);
    }
}
