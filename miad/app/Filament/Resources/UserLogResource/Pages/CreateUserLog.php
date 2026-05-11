<?php

namespace App\Filament\Resources\UserLogResource\Pages;

use App\Filament\Resources\UserLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserLog extends CreateRecord
{
    protected static string $resource = UserLogResource::class;
}
