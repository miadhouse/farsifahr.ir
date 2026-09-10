<?php

namespace App\Filament\Resources\QuestionVideoResource\Pages;

use App\Filament\Resources\QuestionVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageQuestionVideos extends ManageRecords
{
    protected static string $resource = QuestionVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
