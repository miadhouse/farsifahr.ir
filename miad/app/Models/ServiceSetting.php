<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSetting extends Model
{
    protected $connection = 'farsi_fahr2';
    protected $table = 'service_settings';

    protected $fillable = [
        'service_key',
        'title',
        'description',
        'price',
        'whatsapp_message',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
