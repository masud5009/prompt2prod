<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenerationPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_quota',
        'price',
        'currency',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(UserPackagePurchase::class);
    }
}
