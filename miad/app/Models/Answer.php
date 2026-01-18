<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    // مشخص کردن دیتابیس
    protected $connection = 'farsi_fahr2';
    
    // نام جدول
    protected $table = 'answers';
    
    // فیلدهای قابل پر شدن
    protected $fillable = [
        'question_id',
        'text',
        'en_text',
        'farsi_text',
        'info',
        'is_image',
        'original_content',
        'asw_type',
        'asw_corr',
        'asw_hint',
    ];
    
    // اگر timestamps نداشتید
    public $timestamps = false;
    
    // Cast کردن فیلدها
    protected $casts = [
        'is_image' => 'boolean',
        'asw_corr' => 'boolean',
        'question_id' => 'integer',
    ];
    
    // رابطه با جدول questions
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}