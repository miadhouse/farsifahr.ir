<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeSliderResource\Pages;
use App\Filament\Resources\HomeSliderResource\RelationManagers;
use App\Models\HomeSlider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HomeSliderResource extends Resource
{
    protected static ?string $model = HomeSlider::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'اسلایدر صفحه اصلی';
    protected static ?string $pluralModelLabel = 'اسلایدهای صفحه اصلی';
    protected static ?string $modelLabel = 'اسلاید';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('جزئیات اسلاید')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان اسلاید')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image_path')
                            ->label('تصویر اسلاید')
                            ->image()
                            ->disk('public')
                            ->directory('slider')
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('وضعیت فعال')
                            ->default(true),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت فعال')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('وضعیت فعال بودن')
                    ->placeholder('همه')
                    ->trueLabel('فقط فعال')
                    ->falseLabel('فقط غیرفعال'),
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
            'index' => Pages\ListHomeSliders::route('/'),
            'create' => Pages\CreateHomeSlider::route('/create'),
            'edit' => Pages\EditHomeSlider::route('/{record}/edit'),
        ];
    }
}
