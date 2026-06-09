<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopCategoryResource\Pages;
use App\Models\WorkshopCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkshopCategoryResource extends Resource
{
    protected static ?string $model = WorkshopCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'کارگاه آموزش';
    protected static ?string $navigationLabel = 'دسته‌بندی‌ها';
    protected static ?string $pluralModelLabel = 'دسته‌بندی‌های کارگاه';
    protected static ?string $modelLabel = 'دسته‌بندی';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('دسته والد')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('نام دسته‌بندی')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('دسته والد')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('نامک'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListWorkshopCategories::route('/'),
            'create' => Pages\CreateWorkshopCategory::route('/create'),
            'edit' => Pages\EditWorkshopCategory::route('/{record}/edit'),
        ];
    }
}
