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
        Schema::table('pengajuan', function (Blueprint $table) {
            // Approval level: 'biasa' for regular PNS, 'atasan' for supervisors
            $table->enum('approval_level', ['biasa', 'atasan'])->default('biasa')->after('status');

            // For atasan applicants: who approved their application
            $table->unsignedBigInteger('approved_by_atasan')->nullable()->after('approval_level');
            $table->timestamp('approved_at_atasan')->nullable()->after('approved_by_atasan');

            // Foreign key to users table
            $table->foreign('approved_by_atasan')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->dropForeign(['approved_by_atasan']);
            $table->dropColumn(['approval_level', 'approved_by_atasan', 'approved_at_atasan']);
        });
    }
};
