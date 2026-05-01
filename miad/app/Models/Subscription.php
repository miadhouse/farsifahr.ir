<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'user_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'expires_at',
        'duration_days',
        'amount_paid',
        'payment_method',
        'transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'user_id');
    }

    public function plan()
    {
        // با فرض اینکه جدول subscription_plans هم وجود دارد و مدلی برای آن می‌سازیم
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
