<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

class VerificationRule extends Model
{
    #[Fillable(['kode', 'nama_jabatan', 'atasan_level', 'signer_s1', 'signer_s2', 'signer_s3', 'urutan', 'is_active'])]

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get signer by jenjang pendidikan
     */
    public function getSignerForJenjang(string $jenjang): string
    {
        return match($jenjang) {
            'D1', 'D2', 'D3', 'S1' => $this->signer_s1,
            'S2', 'Profesi' => $this->signer_s2,
            'S3' => $this->signer_s3,
            default => $this->signer_s1,
        };
    }

    /**
     * Get rule by jabatan kode
     */
    public static function getByKode(string $kode): ?self
    {
        return static::where('kode', $kode)->where('is_active', true)->first();
    }

    /**
     * Find rule by jabatan name (partial match)
     */
    public static function findByJabatan(string $jabatan): ?self
    {
        return static::where('is_active', true)
            ->where('nama_jabatan', 'like', "%{$jabatan}%")
            ->first();
    }
}
