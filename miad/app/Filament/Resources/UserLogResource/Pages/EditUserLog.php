<?php

namespace App\Filament\Resources\UserLogResource\Pages;

use App\Filament\Resources\UserLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserLog extends EditRecord
{
    protected static string $resource = UserLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
