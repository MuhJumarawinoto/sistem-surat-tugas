<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->after('password', function (Blueprint $table) {
                $table->string('nip')->unique()->nullable();
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerja')->nullOnDelete();
                $table->string('pangkat_gol')->nullable();
                $table->string('jabatan')->nullable();
                $table->timestamp('tanggal_lahir')->nullable();
                $table->string('no_hp')->nullable();
                $table->text('alamat')->nullable();
                $table->boolean('is_active')->default(true);
            });
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn([
                'nip',
                'role_id',
                'unit_kerja_id',
                'pangkat_gol',
                'jabatan',
                'tanggal_lahir',
                'no_hp',
                'alamat',
                'is_active'
            ]);
        });
    }
};
