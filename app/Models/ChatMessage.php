<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'status',
        'controls',
        'revised_prompt',
        'latency_ms',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'controls' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(GeneratedImage::class)->orderBy('created_at');
    }
}
