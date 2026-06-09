<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserQuestionStat extends Model
{
    protected $connection = 'farsi_fahr2';
    
    protected $table = 'user_question_stats';

    protected $fillable = [
        'user_id',
        'question_id',
        'correct',
        'incorrect',
        'last_answer',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'user_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
