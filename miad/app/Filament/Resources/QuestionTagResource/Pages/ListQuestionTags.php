<?php

namespace App\Filament\Resources\QuestionTagResource\Pages;

use App\Filament\Resources\QuestionTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuestionTags extends ListRecords
{
    protected static string $resource = QuestionTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
