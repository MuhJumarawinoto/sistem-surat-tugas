<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes surat_tugas_dinas_id nullable to support simplified flow
     * where Surat Izin Belajar is created before Surat Tugas Dinas.
     */
    public function up(): void
    {
        Schema::table('surat_izin_belajar', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['surat_tugas_dinas_id']);
            // Make the column nullable
            $table->foreignId('surat_tugas_dinas_id')->nullable()->change();
            // Re-add foreign key constraint
            $table->foreign('surat_tugas_dinas_id')
                  ->references('id')
                  ->on('surat_tugas_dinas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_izin_belajar', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['surat_tugas_dinas_id']);
            // Make column required again
            $table->foreignId('surat_tugas_dinas_id')->nullable(false)->change();
            // Re-add foreign key with cascade delete
            $table->foreign('surat_tugas_dinas_id')
                  ->references('id')
                  ->on('surat_tugas_dinas')
                  ->onDelete('cascade');
        });
    }
};
