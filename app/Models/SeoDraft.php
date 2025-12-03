<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_job_id',
        'product_id',
        'original_data',
        'generated_draft',
        'audit_flags',
        'confidence_score',
        'status',
    ];

    protected $casts = [
        'original_data' => 'array',
        'generated_draft' => 'array',
        'audit_flags' => 'array',
        'confidence_score' => 'decimal:4',
    ];

    public function seoJob()
    {
        return $this->belongsTo(SeoJob::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
