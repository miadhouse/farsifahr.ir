<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workshop extends Model
{
    use HasFactory;

    protected $connection = 'farsi_fahr2';

    protected $fillable = [
        'workshop_category_id',
        'title',
        'slug',
        'content',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'workshop_category_id');
    }
}
