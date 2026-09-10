<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionVideo extends Model
{
    // این جدول در دیتابیس دوم (سوالات) قرار دارد
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'question_videos';
    
    protected $fillable = [
        'question_id',
        'video_url',
        'title',
    ];
    
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
