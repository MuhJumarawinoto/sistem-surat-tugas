<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for user_id + is_read (most common query pattern)
            $table->index(['user_id', 'is_read'], 'idx_user_read');

            // Index for created_at (sorting)
            $table->index('created_at', 'idx_created_at');

            // Index for is_read filtering
            $table->index('is_read', 'idx_is_read');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_user_read');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_is_read');
        });
    }
};
