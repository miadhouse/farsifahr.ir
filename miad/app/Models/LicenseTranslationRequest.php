<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseTranslationRequest extends Model
{
    protected $connection = 'farsi_fahr2';
    protected $table = 'license_translation_requests';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'postal_code',
        'city',
        'street',
        'house_number',
        'additional_address',
        'front_image_path',
        'back_image_path',
        'status',
        'price',
        'payment_contact_method',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'user_id');
    }
}
