<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_kepala_unit')->default(false)->after('unit_kerja_id');
            $table->index('unit_kerja_id');
            $table->index(['unit_kerja_id', 'is_kepala_unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['unit_kerja_id', 'is_kepala_unit']);
            $table->dropColumn('is_kepala_unit');
        });
    }
};
