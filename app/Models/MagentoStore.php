<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MagentoStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'api_token',
        'sync_status',
        'total_products',
        'products_fetched',
        'last_sync_started_at',
        'last_sync_completed_at',
        'sync_error',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'last_sync_started_at' => 'datetime',
        'last_sync_completed_at' => 'datetime',
    ];

    protected function apiToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
