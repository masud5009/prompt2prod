<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_message_id',
        'label',
        'url',
        'alt',
        'width',
        'height',
        'prompt',
        'revised_prompt',
        'seed',
        'palette',
        'format',
    ];

    protected function casts(): array
    {
        return [
            'palette' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }
}
