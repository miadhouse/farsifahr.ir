<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopCategory extends Model
{
    use HasFactory;

    protected $connection = 'farsi_fahr2';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WorkshopCategory::class, 'parent_id');
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }
}
