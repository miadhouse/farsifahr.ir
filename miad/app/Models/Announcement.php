<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $connection = 'farsi_fahr2';

    protected $fillable = [
        'title',
        'content',
        'position',
        'target_pages',
        'audience',
        'display_type',
        'custom_views_limit',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'target_pages' => 'array',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];
}
