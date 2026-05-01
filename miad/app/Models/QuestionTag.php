<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionTag extends Model
{
    protected $connection = 'farsi_fahr2';
    protected $fillable = ['name', 'color'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
