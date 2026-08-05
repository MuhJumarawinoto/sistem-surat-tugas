<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'format_nama', 'catatan', 'is_active'])]
class JenisDokumenPga extends Model
{
    protected $table = 'jenis_dokumen_pga';

    protected function casts(): array
    {
        return [
            'is_wajib' => 'boolean',
            'is_active' => 'boolean',
            'persyaratan' => 'array',
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
     * Scope untuk required documents only
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_wajib', true)->where('is_active', true);
    }

    /**
     * Scope untuk admin - semua termasuk non-active
     */
    public function scopeAllWithInactive(Builder $query): Builder
    {
        return $query->orderBy('urutan');
    }

    /**
     * Get field name from kode (remove _file suffix if exists)
     */
    public function getFieldNameAttribute(): string
    {
        return str_replace('_file', '', $this->kode);
    }

    /**
     * Get storage path for this document type
     */
    public function getStoragePathAttribute(): string
    {
        $fieldName = $this->field_name;

        return 'pga/'.$fieldName;
    }
}
