<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'kode', 'urutan', 'is_active'])]
class JenjangPendidikan extends Model
{
    protected $table = 'jenjang_pendidikan';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    /**
     * Get the nama_jenjang attribute (alias for nama).
     */
    public function getNamaJenjangAttribute(): string
    {
        return $this->nama;
    }
}
