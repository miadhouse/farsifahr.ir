<?php

namespace App\Filament\Resources\ServiceSettingResource\Pages;

use App\Filament\Resources\ServiceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceSettings extends ManageRecords
{
    protected static string $resource = ServiceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ما دکمه ساخت جدید نمی‌خواهیم چون فقط همین ۳ تا خدمت وجود دارند و نباید یکی جدید بدون کد اضافه شود
        ];
    }
}
