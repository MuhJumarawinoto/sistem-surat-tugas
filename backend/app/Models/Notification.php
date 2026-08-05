<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'pengajuan_id',
    'pga_id',
    'type',
    'title',
    'message',
    'is_read',
    'read_at',
])]
class Notification extends Model
{
    protected $table = 'notifications';

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function pga(): BelongsTo
    {
        return $this->belongsTo(PgaPengajuan::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function createForUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $pengajuanId = null,
        ?int $pgaId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'pengajuan_id' => $pengajuanId,
            'pga_id' => $pgaId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }
}
