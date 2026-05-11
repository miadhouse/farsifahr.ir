<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteTranslation extends Model
{
    protected $connection = 'farsi_fahr2';
    protected $table = 'site_translations';

    protected $fillable = [
        'trans_key',
        'fa',
        'de',
        'en',
        'trans_group',
    ];
}
