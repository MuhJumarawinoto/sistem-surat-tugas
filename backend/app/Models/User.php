<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'nip', 'role_id', 'unit_kerja_id', 'atasan_id', 'jabatan_kategori', 'pangkat_gol', 'jabatan', 'tanggal_lahir', 'no_hp', 'alamat', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approver_id');
    }

    public function suratTugasSigned(): HasMany
    {
        return $this->hasMany(SuratTugas::class, 'signed_by');
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->slug === $role;
    }

    public function isPemohon(): bool
    {
        return $this->hasRole('pemohon');
    }

    public function isAtasan(): bool
    {
        return $this->hasRole('atasan');
    }

    public function isAdminBkpsdm(): bool
    {
        return $this->hasRole('admin_bkpsdm');
    }

    public function isKepalaBkpsdm(): bool
    {
        return $this->hasRole('kepala_bkpsdm');
    }
}
