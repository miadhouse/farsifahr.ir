<?php

namespace App\Filament\Resources\EyeTestAppointmentRequestResource\Pages;

use App\Filament\Resources\EyeTestAppointmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEyeTestAppointmentRequests extends ManageRecords
{
    protected static string $resource = EyeTestAppointmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // درخواست‌ها فقط باید از سمت بخش کاربری پر شوند
        ];
    }
}
