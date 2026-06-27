<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EyeTestAppointmentRequest extends Model
{
    protected $connection = 'farsi_fahr2';
    protected $table = 'eye_test_appointment_requests';

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
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'user_id');
    }
}
