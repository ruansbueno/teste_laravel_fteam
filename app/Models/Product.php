<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'external_id',
        'title',
        'description',
        'price',
        'image_url',
        'raw_payload',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw_payload' => 'array',
        'external_id' => 'integer'
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }
}
