<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'magento_store_view',
        'product_ids',
        'filter_criteria',
        'llm_config_id',
        'status',
        'total_products',
        'processed_products',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'filter_criteria' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function drafts()
    {
        return $this->hasMany(SeoDraft::class);
    }

    public function llmConfiguration()
    {
        return $this->belongsTo(LlmConfiguration::class, 'llm_config_id');
    }

    public function products()
    {
        if (!$this->product_ids) {
            return Product::query()->whereIn('id', []);
        }

        return Product::query()->whereIn('id', $this->product_ids);
    }

    public function getSelectedProductsAttribute()
    {
        return $this->products()->get();
    }
}
