<?php

namespace App\Filament\Resources\LicenseTranslationRequestResource\Pages;

use App\Filament\Resources\LicenseTranslationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLicenseTranslationRequests extends ManageRecords
{
    protected static string $resource = LicenseTranslationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ما دکمه ساخت جدید از سمت ادمین پنل را نمی‌خواهیم چون درخواست‌ها فقط باید از سمت بخش کاربری پر شوند
        ];
    }
}
