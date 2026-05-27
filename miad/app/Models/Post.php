<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $connection = 'farsi_fahr2';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'author_name',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
