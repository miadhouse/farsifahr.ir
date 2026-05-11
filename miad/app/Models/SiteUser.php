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
        'referral_code',
        'referred_by_id',
    ];

    public function referredUsers()
    {
        return $this->hasMany(SiteUser::class, 'referred_by_id');
    }

    public function referrer()
    {
        return $this->belongsTo(SiteUser::class, 'referred_by_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }
}
