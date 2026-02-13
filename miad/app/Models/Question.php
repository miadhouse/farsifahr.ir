<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    // مشخص کردن دیتابیس
    protected $connection = 'farsi_fahr2';
    
    // نام جدول
    protected $table = 'questions';
    
    // فیلدهای قابل پر شدن
    protected $fillable = [
        'number',
        'picture',
        'stvo',
        'asw_pretext',
        'asw_farsi',
        'asw_en',
        'points',
        'basic',
        'basic_mofa',
        'mq_flag',
        'category_id',
        'classes',
        'text',
        'en_text',
        'farsi_text',
        'info', 
    
        'available',
    ];
    
    // اگر timestamps نداشتید
    public $timestamps = false;
    
    // Cast کردن فیلدها
    protected $casts = [
        'number' => 'string',
        'available' => 'boolean',
        'basic' => 'boolean',
        'basic_mofa' => 'boolean',
        'mq_flag' => 'boolean',
        'points' => 'integer',
        'category_id' => 'integer',
    ];
        // رابطه با جدول categories (اگر دارید)
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
// رابطه با جدول answers
public function answers()
{
    return $this->hasMany(Answer::class, 'question_number', 'number');
}
}