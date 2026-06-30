<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'catatan', 'is_active'])]
class JenisDokumen extends Model
{
    protected $table = 'jenis_dokumen';

    protected function casts(): array
    {
        return [
            'is_wajib' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope untuk active documents only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }

    /**
     * Scope untuk admin - semua termasuk non-active
     */
    public function scopeAllWithInactive(Builder $query): Builder
    {
        return $query->orderBy('urutan');
    }
}
