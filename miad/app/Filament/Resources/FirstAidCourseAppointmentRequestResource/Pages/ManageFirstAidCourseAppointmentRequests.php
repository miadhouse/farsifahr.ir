<?php

namespace App\Filament\Resources\FirstAidCourseAppointmentRequestResource\Pages;

use App\Filament\Resources\FirstAidCourseAppointmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFirstAidCourseAppointmentRequests extends ManageRecords
{
    protected static string $resource = FirstAidCourseAppointmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // درخواست‌ها فقط باید از سمت بخش کاربری پر شوند
        ];
    }
}
