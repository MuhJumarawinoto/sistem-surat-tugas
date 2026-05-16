<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_role(): void
    {
        $role = Role::create(['name' => 'Pemohon', 'slug' => 'pemohon']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals('pemohon', $user->role->slug);
    }

    public function test_user_belongs_to_unit_kerja(): void
    {
        $unitKerja = UnitKerja::create(['kode' => 'UK01', 'nama' => 'BKPSDM']);
        $user = User::factory()->create(['unit_kerja_id' => $unitKerja->id]);

        $this->assertInstanceOf(UnitKerja::class, $user->unitKerja);
        $this->assertEquals('BKPSDM', $user->unitKerja->nama);
    }

    public function test_has_role_returns_true_when_role_matches(): void
    {
        $role = Role::create(['name' => 'Pemohon', 'slug' => 'pemohon']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasRole('pemohon'));
    }

    public function test_has_role_returns_false_when_role_does_not_match(): void
    {
        $role = Role::create(['name' => 'Pemohon', 'slug' => 'pemohon']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertFalse($user->hasRole('admin_bkpsdm'));
    }

    public function test_has_role_returns_false_when_no_role(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertFalse($user->hasRole('pemohon'));
    }

    public function test_is_pemohon(): void
    {
        $role = Role::create(['name' => 'Pemohon', 'slug' => 'pemohon']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->isPemohon());
        $this->assertFalse($user->isAtasan());
        $this->assertFalse($user->isAdminBkpsdm());
        $this->assertFalse($user->isKepalaBkpsdm());
    }

    public function test_is_atasan(): void
    {
        $role = Role::create(['name' => 'Atasan Langsung', 'slug' => 'atasan']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->isAtasan());
        $this->assertFalse($user->isPemohon());
    }

    public function test_is_admin_bkpsdm(): void
    {
        $role = Role::create(['name' => 'Admin BKPSDM', 'slug' => 'admin_bkpsdm']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->isAdminBkpsdm());
        $this->assertFalse($user->isPemohon());
    }

    public function test_is_kepala_bkpsdm(): void
    {
        $role = Role::create(['name' => 'Kepala BKPSDM', 'slug' => 'kepala_bkpsdm']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->isKepalaBkpsdm());
        $this->assertFalse($user->isPemohon());
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain_password']);

        $this->assertNotEquals('plain_password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plain_password', $user->password));
    }

    public function test_is_active_casts_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $this->assertTrue($user->is_active);
        $this->assertFalse($inactiveUser->is_active);
    }

    public function test_user_has_pengajuan_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->pengajuan);
        $this->assertCount(0, $user->pengajuan);
    }

    public function test_user_has_approval_history_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->approvalHistory);
    }

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
