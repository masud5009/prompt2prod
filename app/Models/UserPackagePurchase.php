<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'generation_package_id',
        'purchased_images',
        'used_images',
        'status',
        'purchased_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(GenerationPackage::class, 'generation_package_id');
    }

    public function getRemainingImagesAttribute(): int
    {
        return max(0, $this->purchased_images - $this->used_images);
    }
}
