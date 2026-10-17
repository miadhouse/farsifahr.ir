<?php

namespace App\Filament\Resources\DatabaseBackupResource\Pages;

use App\Filament\Resources\DatabaseBackupResource;
use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageDatabaseBackups extends ManageRecords
{
    protected static string $resource = DatabaseBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_backup')
                ->label('ایجاد بکاپ دستی')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('ایجاد نسخه پشتیبان جدید از دیتابیس')
                ->modalDescription('دیتابیس مورد نظر برای تهیه فایل پشتیبان فشرده را انتخاب کنید.')
                ->modalSubmitActionLabel('شروع پشتیبان‌گیری')
                ->form([
                    Select::make('target_database')
                        ->label('انتخاب دیتابیس')
                        ->options([
                            'fars_farsifahr' => 'دیتابیس اصلی سایت (fars_farsifahr)',
                            'fars_lara' => 'دیتابیس پنل مدیریت (fars_lara)',
                            'all' => 'هر دو دیتابیس (کامل)',
                        ])
                        ->default('fars_farsifahr')
                        ->required(),
                    Toggle::make('send_to_telegram')
                        ->label('ارسال مستقیم نسخه بکاپ به کانال تلگرام')
                        ->helperText('پس از اتمام بکاپ، فایل فشرده به کانال تلگرام مشخص شده در تنظیمات ارسال خواهد شد.')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $result = BackupService::createBackup(
                        $data['target_database'],
                        (bool) ($data['send_to_telegram'] ?? false)
                    );

                    if ($result['success']) {
                        $msg = $result['message'];
                        if (!empty($data['send_to_telegram'])) {
                            $tg = $result['telegram'] ?? null;
                            if ($tg && $tg['success']) {
                                $msg .= ' و به تلگرام ارسال شد.';
                            } else {
                                $msg .= ' (اما ارسال به تلگرام با خطا مواجه شد: ' . ($tg['error'] ?? '') . ')';
                            }
                        }

                        Notification::make()
                            ->title('پشتیبان‌گیری موفق')
                            ->body($msg)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('خطا در پشتیبان‌گیری')
                            ->body($result['error'] ?? 'خطای نامشخص')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sync_files')
                ->label('همگام‌سازی فایل‌های سرور')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('اسکن پوشه سرور و شناسایی فایل‌های بکاپ موجود')
                ->action(function () {
                    $synced = BackupService::syncFromDisk();
                    Notification::make()
                        ->title('بروزرسانی فایل‌ها')
                        ->body("لیست فایل‌ها بررسی و همگام‌سازی شد ({$synced} فایل جدید ثبت شد).")
                        ->success()
                        ->send();
                }),

            Action::make('settings')
                ->label('تنظیمات بکاپ و تلگرام')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->modalHeading('تنظیمات بکاپ خودکار روزانه و تلگرام')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('ذخیره تنظیمات')
                ->fillForm(fn () => BackupService::getSettings())
                ->form([
                    Section::make('زمان‌بندی خودکار روزانه')
                        ->schema([
                            Toggle::make('backup_daily_enabled')
                                ->label('فعال بودن بکاپ اتوماتیک روزانه')
                                ->helperText('در صورت فعال بودن، سیستم هر روز در ساعت تعیین شده به طور خودکار بکاپ می‌گیرد.')
                                ->default(true),
                            Grid::make(3)->schema([
                                TextInput::make('backup_time')
                                    ->label('ساعت اجرا (فرمت 24 ساعته)')
                                    ->placeholder('03:00')
                                    ->helperText('مثال: 03:00')
                                    ->default('03:00')
                                    ->required(),
                                Select::make('backup_database')
                                    ->label('دیتابیس بکاپ خودکار')
                                    ->options([
                                        'fars_farsifahr' => 'دیتابیس اصلی (fars_farsifahr)',
                                        'fars_lara' => 'دیتابیس پنل (fars_lara)',
                                        'all' => 'هر دو دیتابیس',
                                    ])
                                    ->default('fars_farsifahr')
                                    ->required(),
                                TextInput::make('backup_retention_days')
                                    ->label('نگهداری فایل‌ها (روز)')
                                    ->helperText('حذف فایل‌های قدیمی‌تر')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                            ]),
                        ]),

                    Section::make('تنظیمات ارسال خودکار به تلگرام')
                        ->schema([
                            Toggle::make('backup_telegram_enabled')
                                ->label('ارسال خودکار فایل بکاپ به تلگرام')
                                ->helperText('ارسال فایل فشرده به تلگرام بلافاصله پس از تهیه بکاپ روزانه')
                                ->default(true),
                            Grid::make(2)->schema([
                                TextInput::make('telegram_bot_token')
                                    ->label('توکن ربات تلگرام')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                                TextInput::make('backup_telegram_channel_id')
                                    ->label('شناسه چت/کانال دریافت بکاپ (یوزر ترکینگ)')
                                    ->placeholder('5287816017')
                                    ->helperText('آیدی عددی چت یا کانال اختصاصی مدیر (پیش‌فرض: چت ترکینگ 5287816017).')
                                    ->required(),
                            ]),
                        ]),
                ])
                ->action(function (array $data) {
                    BackupService::saveSettings($data);
                    Notification::make()
                        ->title('تنظیمات با موفقیت ذخیره شد')
                        ->success()
                        ->send();
                }),
        ];
    }
}
