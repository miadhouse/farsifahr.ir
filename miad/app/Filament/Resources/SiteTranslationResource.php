<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteTranslationResource\Pages;
use App\Models\SiteTranslation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteTranslationResource extends Resource
{
    protected static ?string $model = SiteTranslation::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $label = 'ترجمه سایت';
    protected static ?string $pluralLabel = 'ترجمه های سایت';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('trans_key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('کلید ترجمه')
                    ->placeholder('e.g. welcome_title'),
                Forms\Components\TextInput::make('trans_group')
                    ->label('گروه')
                    ->default('common'),
                Forms\Components\Textarea::make('fa')
                    ->label('فارسی (FA)')
                    ->required(),
                Forms\Components\Textarea::make('de')
                    ->label('آلمانی (DE)')
                    ->required(),
                Forms\Components\Textarea::make('en')
                    ->label('انگلیسی (EN)')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trans_key')
                    ->searchable()
                    ->sortable()
                    ->label('کلید'),
                Tables\Columns\TextColumn::make('trans_group')
                    ->searchable()
                    ->label('گروه'),
                Tables\Columns\TextColumn::make('fa')
                    ->limit(50)
                    ->label('فارسی'),
                Tables\Columns\TextColumn::make('de')
                    ->limit(50)
                    ->label('آلمانی'),
                Tables\Columns\TextColumn::make('en')
                    ->limit(50)
                    ->label('انگلیسی'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trans_group')
                    ->options([
                        'common' => 'عمومی',
                        'home' => 'صفحه اصلی',
                        'dashboard' => 'داشبورد',
                    ])
                    ->label('فیلتر گروه'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSiteTranslations::route('/'),
        ];
    }
}
