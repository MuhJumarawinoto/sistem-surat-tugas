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
        Schema::table('surat_izin_belajar', function (Blueprint $table) {
            $table->string('signed_by_nip')->nullable()->after('signed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_izin_belajar', function (Blueprint $table) {
            $table->dropColumn('signed_by_nip');
        });
    }
};
