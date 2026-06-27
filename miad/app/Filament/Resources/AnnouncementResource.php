<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'اعلان‌ها';
    protected static ?string $pluralModelLabel = 'اعلان‌ها';
    protected static ?string $modelLabel = 'اعلان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('جزئیات اعلان')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان اعلان')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('position')
                            ->label('موقعیت نمایش در صفحه')
                            ->options([
                                'top' => 'نوار بالا (Top Banner)',
                                'middle' => 'وسط صفحه به صورت کادر (Popup Modal)',
                                'bottom' => 'نوار پایین (Bottom Banner)',
                            ])
                            ->required(),
                        Forms\Components\Select::make('target_pages')
                            ->label('صفحات هدف جهت نمایش')
                            ->multiple()
                            ->options([
                                'home' => 'صفحه نخست',
                                'dashboard' => 'داشبورد',
                                'practice' => 'صفحه تمرین و مرور',
                                'exam' => 'شبیه‌ساز امتحان',
                            ])
                            ->required(),
                        Forms\Components\Select::make('audience')
                            ->label('نمایش برای مخاطبین')
                            ->options([
                                'all' => 'هم اعضا و هم مهمانان',
                                'members' => 'فقط کاربران عضو',
                                'guests' => 'فقط کاربران مهمان',
                            ])
                            ->required(),
                        Forms\Components\Select::make('display_type')
                            ->label('دوره نمایش')
                            ->options([
                                'once' => 'فقط ۱ بار',
                                'three_times' => '۳ بار',
                                'always' => 'همیشگی',
                                'custom' => 'تعداد دفعات سفارشی',
                                'until_date' => 'تا تاریخ مشخص',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('custom_views_limit')
                            ->label('حداکثر دفعات نمایش برای هر کاربر')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Forms\Get $get) => $get('display_type') === 'custom')
                            ->required(fn (Forms\Get $get) => $get('display_type') === 'custom'),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('تاریخ پایان نمایش')
                            ->visible(fn (Forms\Get $get) => in_array($get('display_type'), ['until_date', 'custom', 'once', 'three_times']))
                            ->required(fn (Forms\Get $get) => $get('display_type') === 'until_date'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال بودن اعلان')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('طراحی و محتوا')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('محتوای اعلان')
                            ->helperText('می‌توانید از تصاویر، گیف‌ها، انیمیشن‌ها، ویدیوها و ویرایشگر پیشرفته برای طراحی محتوا استفاده کنید.')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('announcements-media'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('موقعیت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'top' => 'primary',
                        'middle' => 'warning',
                        'bottom' => 'success',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'top' => 'نوار بالا',
                        'middle' => 'وسط صفحه',
                        'bottom' => 'نوار پایین',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('audience')
                    ->label('مخاطبین')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'همه',
                        'members' => 'اعضا',
                        'guests' => 'مهمانان',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('display_type')
                    ->label('دوره نمایش')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'once' => 'فقط ۱ بار',
                        'three_times' => '۳ بار',
                        'always' => 'همیشگی',
                        'custom' => 'دفعات سفارشی',
                        'until_date' => 'تا تاریخ مشخص',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\ToggledFilter::make('is_active')
                    ->label('فقط فعال‌ها'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
