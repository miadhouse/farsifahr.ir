<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'user_logs';

    public $timestamps = false; // Because we only have created_at, or handle it manually. But Laravel expects created_at and updated_at by default.
    
    protected $fillable = [
        'user_id',
        'email',
        'action',
        'ip_address',
        'location',
        'user_agent',
        'status',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function siteUser(): BelongsTo
    {
        return $this->belongsTo(SiteUser::class, 'user_id', 'id');
    }
}
