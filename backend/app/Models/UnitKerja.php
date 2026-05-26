<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'singkatan', 'eselon', 'alamat', 'telepon', 'email', 'is_active'])]
class UnitKerja extends Model
{
    protected $table = 'unit_kerja';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the nama_unit_kerja attribute (alias for nama).
     */
    public function getNamaUnitKerjaAttribute(): string
    {
        return $this->nama;
    }
}
