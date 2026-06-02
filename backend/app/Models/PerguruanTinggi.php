<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerguruanTinggi extends Model
{
    protected $table = 'perguruan_tinggi';

    protected $fillable = [
        'kode_pt',
        'nama_pt',
        'nama_singkat',
        'jenis_perguruan_tinggi',
        'alamat',
        'provinsi',
        'kab_kota',
        'kecamatan',
        'kode_pos',
        'website',
        'telepon',
        'email',
        'akreditasi',
        'status_pt',
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    public function prodis(): HasMany
    {
        return $this->hasMany(Prodi::class);
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('nama_pt', 'like', "%{$keyword}%")
            ->orWhere('kode_pt', 'like', "%{$keyword}%")
            ->orWhere('nama_singkat', 'like', "%{$keyword}%");
    }
}
