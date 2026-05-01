<?php

namespace App\Filament\Resources\QuestionTagResource\Pages;

use App\Filament\Resources\QuestionTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestionTag extends EditRecord
{
    protected static string $resource = QuestionTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
