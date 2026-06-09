<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConfig extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'user_config';

    protected $fillable = [
        'user_id',
        'exam_date_type',
        'language',
        'is_configured',
        'reference_date',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'user_id');
    }
}
