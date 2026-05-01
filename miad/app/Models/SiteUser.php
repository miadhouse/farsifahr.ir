<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteUser extends Model
{
    protected $connection = 'farsi_fahr2'; // اتصال به دیتابیس سایت اصلی
    
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'role',
        'google_id',
        'email_verified',
    ];
}
