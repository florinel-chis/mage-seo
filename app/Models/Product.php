<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'type_id',
        'name',
        'description',
        'attributes',
        'extension_attributes',
    ];

    protected $casts = [
        'attributes' => 'array',
        'extension_attributes' => 'array',
    ];
}
