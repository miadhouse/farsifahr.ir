<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseTranslationRequestResource\Pages;
use App\Models\LicenseTranslationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LicenseTranslationRequestResource extends Resource
{
    protected static ?string $model = LicenseTranslationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $label = 'درخواست ترجمه گواهینامه';
    protected static ?string $pluralLabel = 'درخواست‌های ترجمه گواهینامه';
    protected static ?string $navigationGroup = 'مدیریت خدمات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کاربری')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('نام')
                            ->disabled(),
                        Forms\Components\TextInput::make('last_name')
                            ->label('نام خانوادگی')
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->label('تلفن تماس')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('ایمیل')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('آدرس پستی')
                    ->schema([
                        Forms\Components\TextInput::make('postal_code')
                            ->label('کد پستی')
                            ->disabled(),
                        Forms\Components\TextInput::make('city')
                            ->label('شهر')
                            ->disabled(),
                        Forms\Components\TextInput::make('street')
                            ->label('خیابان')
                            ->disabled(),
                        Forms\Components\TextInput::make('house_number')
                            ->label('پلاک')
                            ->disabled(),
                        Forms\Components\TextInput::make('additional_address')
                            ->label('جزئیات بیشتر آدرس')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('مدارک و وضعیت درخواست')
                    ->schema([
                        Forms\Components\Placeholder::make('front_image')
                            ->label('تصویر روی گواهینامه')
                            ->content(fn ($record) => $record ? new \Illuminate\Support\HtmlString('<a href="/download-license.php?file=' . urlencode($record->front_image_path) . '" target="_blank" class="text-primary-600 underline font-semibold" style="color:#2563eb;text-decoration:underline;">دانلود/مشاهده تصویر روی گواهینامه</a>') : '-'),
                        
                        Forms\Components\Placeholder::make('back_image')
                            ->label('تصویر پشت گواهینامه')
                            ->content(fn ($record) => $record ? new \Illuminate\Support\HtmlString('<a href="/download-license.php?file=' . urlencode($record->back_image_path) . '" target="_blank" class="text-primary-600 underline font-semibold" style="color:#2563eb;text-decoration:underline;">دانلود/مشاهده تصویر پشت گواهینامه</a>') : '-'),

                        Forms\Components\Select::make('status')
                            ->label('وضعیت درخواست')
                            ->options([
                                'pending_payment' => 'در انتظار پرداخت',
                                'pending_review' => 'در انتظار بررسی',
                                'processing' => 'در حال ترجمه',
                                'shipped' => 'ارسال شده',
                                'completed' => 'تکمیل شده',
                                'cancelled' => 'لغو شده',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('قیمت (یورو)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('payment_contact_method')
                            ->label('روش تماس برای پرداخت')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('نام خانوادگی')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_payment' => 'warning',
                        'pending_review' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'gray',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_payment' => 'در انتظار پرداخت',
                        'pending_review' => 'در انتظار بررسی',
                        'processing' => 'در حال ترجمه',
                        'shipped' => 'ارسال شده',
                        'completed' => 'تکمیل شده',
                        'cancelled' => 'لغو شده',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت (EUR)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending_payment' => 'در انتظار پرداخت',
                        'pending_review' => 'در انتظار بررسی',
                        'processing' => 'در حال ترجمه',
                        'shipped' => 'ارسال شده',
                        'completed' => 'تکمیل شده',
                        'cancelled' => 'لغو شده',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLicenseTranslationRequests::route('/'),
        ];
    }
}
