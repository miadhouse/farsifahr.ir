<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserLogResource\Pages;
use App\Models\UserLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserLogResource extends Resource
{
    protected static ?string $model = UserLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'لاگ فعالیت کاربران';
    protected static ?string $modelLabel = 'لاگ کاربر';
    protected static ?string $pluralModelLabel = 'لاگ فعالیت کاربران';
    protected static ?string $navigationGroup = 'مدیریت کاربران';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->label('ایمیل')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label('عملیات')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('وضعیت')
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('آدرس IP')
                    ->disabled(),
                Forms\Components\TextInput::make('location')
                    ->label('منطقه جغرافیایی')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('مرورگر/دستگاه')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('تاریخ و زمان')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('عملیات')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'info',
                        'register' => 'success',
                        'password_reset_request' => 'warning',
                        'google_login' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'login' => 'ورود',
                        'register' => 'ثبت نام',
                        'password_reset_request' => 'درخواست فراموشی رمز',
                        'google_login' => 'ورود با گوگل',
                        default => $state,
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'موفق',
                        'failed' => 'ناموفق',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('آدرس IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('منطقه')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y/m/d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('نوع عملیات')
                    ->options([
                        'login' => 'ورود',
                        'register' => 'ثبت نام',
                        'password_reset_request' => 'درخواست فراموشی رمز',
                        'google_login' => 'ورود با گوگل',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'success' => 'موفق',
                        'failed' => 'ناموفق',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListUserLogs::route('/'),
            // 'create' => Pages\CreateUserLog::route('/create'),
            // 'edit' => Pages\EditUserLog::route('/{record}/edit'),
        ];
    }
}
