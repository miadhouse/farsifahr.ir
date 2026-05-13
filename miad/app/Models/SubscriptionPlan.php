<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'features',
        'durations',
        'price_2_weeks', 
        'price_1_month', 
        'price_3_months', 
        'price_6_months', 
        'price_1_year', 
        'question_limit', 
        'is_active', 
        'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'durations' => 'array',
    ];
}
