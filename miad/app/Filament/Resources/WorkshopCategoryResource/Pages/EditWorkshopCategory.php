<?php

namespace App\Filament\Resources\WorkshopCategoryResource\Pages;

use App\Filament\Resources\WorkshopCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopCategory extends EditRecord
{
    protected static string $resource = WorkshopCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
