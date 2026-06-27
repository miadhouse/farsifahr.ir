<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EyeTestAppointmentRequestResource\Pages;
use App\Models\EyeTestAppointmentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EyeTestAppointmentRequestResource extends Resource
{
    protected static ?string $model = EyeTestAppointmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $label = 'درخواست نوبت تست چشم';
    protected static ?string $pluralLabel = 'درخواست‌های نوبت تست چشم';
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

                Forms\Components\Section::make('وضعیت درخواست')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('وضعیت')
                            ->options([
                                'pending' => 'در انتظار بررسی',
                                'approved' => 'تایید و رزرو شده',
                                'completed' => 'تکمیل شده',
                                'cancelled' => 'لغو شده',
                            ])
                            ->required(),
                    ])->columns(1),
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
                        'pending' => 'warning',
                        'approved' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'در انتظار بررسی',
                        'approved' => 'تایید و رزرو شده',
                        'completed' => 'تکمیل شده',
                        'cancelled' => 'لغو شده',
                        default => $state,
                    })
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
                        'pending' => 'در انتظار بررسی',
                        'approved' => 'تایید و رزرو شده',
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
            'index' => Pages\ManageEyeTestAppointmentRequests::route('/'),
        ];
    }
}
