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
                Tables\Columns\TextColumn::make('progress')
                    ->label('پیشرفت')
                    ->getStateUsing(function ($record) {
                        static $totalQuestionsCache = [];
                        
                        $config = $record->config;
                        if (!$config) return 'N/A';
                        
                        $availableFilter = ($config->exam_date_type === 'before') ? 1 : 2;
                        
                        if (!isset($totalQuestionsCache[$availableFilter])) {
                            $totalQuestionsCache[$availableFilter] = \App\Models\Question::whereIn('available', [0, $availableFilter])->count();
                        }
                        
                        $totalQuestions = $totalQuestionsCache[$availableFilter];
                        if ($totalQuestions === 0) return '0%';

                        $stats = $record->questionStats()
                            ->whereHas('question', function($query) use ($availableFilter) {
                                $query->whereIn('available', [0, $availableFilter]);
                            })
                            ->get();

                        $green = $stats->filter(fn($s) => $s->correct >= 2 && $s->incorrect == 0)->count();
                        $blue = $stats->filter(fn($s) => $s->correct == 1 && $s->incorrect == 0)->count();
                        $yellow = $stats->filter(fn($s) => ($s->correct > 0 && $s->incorrect > 0) || ($s->correct == 1 && $s->incorrect >= 1))->count();

                        $readyScore = ($green * 100) + ($blue * 50) + ($yellow * 25);
                        $readinessPercentage = round(($readyScore / ($totalQuestions * 100)) * 100);

                        return $readinessPercentage . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => (int)$state >= 70 ? 'success' : ((int)$state >= 30 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('practiced_count')
                    ->label('تمرین شده')
                    ->getStateUsing(function ($record) {
                        return $record->questionStats()->count();
                    }),
                Tables\Columns\TextColumn::make('correct_count')
                    ->label('صحیح')
                    ->getStateUsing(function ($record) {
                        return $record->questionStats()->where('correct', '>', 0)->where('incorrect', 0)->count();
                    })
                    ->color('success'),
                Tables\Columns\TextColumn::make('incorrect_count')
                    ->label('غلط')
                    ->getStateUsing(function ($record) {
                        return $record->questionStats()->where('incorrect', '>', 0)->count();
                    })
                    ->color('danger'),
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