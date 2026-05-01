<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'subscription_plans';

    protected $fillable = ['name', 'price', 'duration_days'];
}
