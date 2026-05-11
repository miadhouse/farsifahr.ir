<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'plan']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'سفارش‌ها و اشتراک‌ها';

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارش‌ها';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('جزئیات سفارش')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('کاربر')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('plan_id')
                            ->label('پلن')
                            ->relationship('plan', 'name')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('وضعیت')
                            ->options([
                                'pending' => 'در انتظار',
                                'active' => 'فعال',
                                'expired' => 'منقضی شده',
                                'cancelled' => 'لغو شده',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('تاریخ انقضا'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('مبلغ پرداختی')
                            ->numeric(),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('شناسه تراکنش'),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('ایمیل کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('پلن')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->sortable()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'cancelled',
                        'secondary' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'در انتظار',
                        'active' => 'فعال',
                        'expired' => 'منقضی',
                        'cancelled' => 'لغو شده',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('انقضا')
                    ->dateTime()
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
                        'pending' => 'در انتظار',
                        'active' => 'فعال',
                        'expired' => 'منقضی',
                        'cancelled' => 'لغو شده',
                    ]),
            ])
            ->actions([
                // اکشن تایید
                Action::make('approve')
                    ->label('تایید')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Subscription $record) => $record->status === 'active')
                    ->action(function (Subscription $record) {
                        $record->update([
                            'status' => 'active',
                            'expires_at' => Carbon::now()->addDays($record->duration_days ?: 30),
                        ]);

                        // اعمال هدیه معرف در صورت وجود
                        if ($record->referred_by_id && $record->referral_bonus_applied == 0) {
                            $bonusNewUser = (int)(DB::connection('farsi_fahr2')->table('settings')->where('key', 'referral_reward_new_user')->value('value') ?? 7);
                            $bonusReferrer = (int)(DB::connection('farsi_fahr2')->table('settings')->where('key', 'referral_reward_referrer')->value('value') ?? 14);

                            // ۱. هدیه به خود کاربر
                            if ($bonusNewUser > 0) {
                                $record->update([
                                    'expires_at' => Carbon::parse($record->expires_at)->addDays($bonusNewUser),
                                    'duration_days' => $record->duration_days + $bonusNewUser,
                                ]);
                            }

                            // ۲. هدیه به معرف
                            if ($bonusReferrer > 0) {
                                $referrer = \App\Models\SiteUser::find($record->referred_by_id);
                                if ($referrer) {
                                    $referrerSub = $referrer->subscriptions()->where('status', 'active')->orderBy('created_at', 'desc')->first();
                                    if ($referrerSub) {
                                        $referrerSub->update([
                                            'expires_at' => Carbon::parse($referrerSub->expires_at)->addDays($bonusReferrer),
                                            'duration_days' => $referrerSub->duration_days + $bonusReferrer,
                                        ]);
                                    } else {
                                        // ایجاد اشتراک جدید برای معرف
                                        $vipPlan = \App\Models\SubscriptionPlan::where('slug', 'vip')->first();
                                        if ($vipPlan) {
                                            \App\Models\Subscription::create([
                                                'user_id' => $referrer->id,
                                                'plan_id' => $vipPlan->id,
                                                'status' => 'active',
                                                'expires_at' => Carbon::now()->addDays($bonusReferrer),
                                                'duration_days' => $bonusReferrer,
                                                'amount_paid' => 0,
                                            ]);
                                            $referrer->update(['current_plan_id' => $vipPlan->id]);
                                        }
                                    }
                                }
                            }

                            $record->update(['referral_bonus_applied' => 1]);
                            
                            // ثبت معرف برای کاربر اگر قبلا نداشته
                            if (!$record->user->referred_by_id) {
                                $record->user->update(['referred_by_id' => $record->referred_by_id]);
                            }
                        }

                        Notification::make()->title('سفارش تایید و فعال شد')->success()->send();
                    }),

                // اکشن توقف
                Action::make('stop')
                    ->label('توقف')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (Subscription $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        $record->update(['status' => 'pending']);
                        Notification::make()->title('اشتراک متوقف شد')->warning()->send();
                    }),

                // اکشن تمدید
                Action::make('renew')
                    ->label('تمدید')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('days')
                            ->label('تعداد روز تمدید')
                            ->numeric()
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $currentExpiry = $record->expires_at ? Carbon::parse($record->expires_at) : Carbon::now();
                        if ($currentExpiry->isPast()) {
                            $currentExpiry = Carbon::now();
                        }
                        $record->update([
                            'status' => 'active',
                            'expires_at' => $currentExpiry->addDays($data['days']),
                        ]);
                        Notification::make()->title('اشتراک با موفقیت تمدید شد')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}