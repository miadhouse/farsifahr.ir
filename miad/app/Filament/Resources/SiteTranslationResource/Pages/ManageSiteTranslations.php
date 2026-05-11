<?php

namespace App\Filament\Resources\SiteTranslationResource\Pages;

use App\Filament\Resources\SiteTranslationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSiteTranslations extends ManageRecords
{
    protected static string $resource = SiteTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
