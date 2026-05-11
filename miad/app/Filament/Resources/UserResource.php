<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\SiteUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = SiteUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'کاربران سایت';

    protected static ?string $modelLabel = 'کاربر سایت';

    protected static ?string $pluralModelLabel = 'کاربران سایت';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کاربری سایت')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام و نام خانوادگی')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->label('نقش')
                            ->options([
                                'user' => 'کاربر عادی',
                                'admin' => 'مدیر سایت',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('email_verified')
                            ->label('تایید ایمیل'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('کد معرف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referred_users_count')
                    ->label('تعداد معرفی')
                    ->counts('referredUsers'),
                Tables\Columns\TextColumn::make('purchased_referrals_count')
                    ->label('معرفی (خرید کرده)')
                    ->getStateUsing(function ($record) {
                        return $record->referredUsers()
                            ->whereHas('subscriptions', function ($query) {
                                $query->whereIn('status', ['active', 'expired']);
                            })->count();
                    }),
                Tables\Columns\TextColumn::make('role')
                    ->label('نقش')
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified')
                    ->label('تایید')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->dateTime()
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}