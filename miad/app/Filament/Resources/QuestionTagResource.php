<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionTagResource\Pages;
use App\Filament\Resources\QuestionTagResource\RelationManagers;
use App\Models\QuestionTag;
use Filament\Forms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionTagResource extends Resource
{
    protected static ?string $model = QuestionTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'دسته‌های خاص (تگ‌ها)';
    protected static ?string $pluralModelLabel = 'دسته‌های خاص';
    protected static ?string $modelLabel = 'دسته خاص';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('نام دسته')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                ColorPicker::make('color')
                    ->label('رنگ نمایش')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام دسته')
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('رنگ نمایش'),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionTags::route('/'),
            'create' => Pages\CreateQuestionTag::route('/create'),
            'edit' => Pages\EditQuestionTag::route('/{record}/edit'),
        ];
    }
}
