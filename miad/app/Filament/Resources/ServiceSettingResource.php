<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceSettingResource\Pages;
use App\Models\ServiceSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceSettingResource extends Resource
{
    protected static ?string $model = ServiceSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $label = 'تنظیمات خدمات';
    protected static ?string $pluralLabel = 'تنظیمات خدمات';
    protected static ?string $navigationGroup = 'مدیریت خدمات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('جزئیات تنظیمات خدمت')
                    ->schema([
                        Forms\Components\TextInput::make('service_key')
                            ->label('کلید خدمت')
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان خدمت')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات معرفی خدمت')
                            ->rows(4)
                            ->required(),
                        Forms\Components\TextInput::make('price')
                            ->label('قیمت (یورو)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('whatsapp_message')
                            ->label('متن پیام پیش‌فرض واتس‌اپ')
                            ->rows(3)
                            ->placeholder('مثال: سلام من درخواست ترجمه گواهینامه ثبت کردم...'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال بودن خدمت')
                            ->default(true),
                    ])->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان خدمت')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_key')
                    ->label('کلید')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت (یورو)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServiceSettings::route('/'),
        ];
    }
}
