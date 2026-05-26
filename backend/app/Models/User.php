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

#[Fillable(['name', 'email', 'password', 'nip', 'role_id', 'unit_kerja_id', 'atasan_id', 'jabatan_kategori', 'pangkat_gol', 'jabatan', 'tanggal_lahir', 'no_hp', 'alamat', 'is_active', 'is_kepala_unit'])]
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
            'is_kepala_unit' => 'boolean',
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

    public function suratTugasDinasCreated(): HasMany
    {
        return $this->hasMany(SuratTugasDinas::class, 'kepala_dinas_id');
    }

    public function isKepalaUnit(): bool
    {
        return $this->is_kepala_unit === true && $this->unit_kerja_id !== null;
    }

    public function hasRole(string $role): bool
    {
        // If relation is loaded, use it
        if ($this->relationLoaded('role') && $this->role) {
            return $this->role->slug === $role;
        }

        // Otherwise check via role_id
        if (!$this->role_id) {
            return false;
        }

        // Get role slug from cache or database
        static $roleCache = [];
        if (!isset($roleCache[$this->role_id])) {
            $roleCache[$this->role_id] = Role::find($this->role_id)?->slug;
        }

        return $roleCache[$this->role_id] === $role;
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
