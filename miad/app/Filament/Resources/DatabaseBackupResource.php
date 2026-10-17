<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DatabaseBackupResource\Pages;
use App\Models\DatabaseBackup;
use App\Services\BackupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use IntlDateFormatter;

class DatabaseBackupResource extends Resource
{
    protected static ?string $model = DatabaseBackup::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'بکاپ دیتابیس';
    protected static ?string $modelLabel = 'بکاپ دیتابیس';
    protected static ?string $pluralModelLabel = 'بکاپ‌های دیتابیس';

    protected static ?string $navigationGroup = 'تنظیمات سیستم';
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        // Sync any new files on disk automatically when viewing table
        BackupService::syncFromDisk();

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('نام فایل')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('نام فایل کپی شد')
                    ->icon('heroicon-o-document-arrow-down')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('database_name')
                    ->label('دیتابیس')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('حجم فایل')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('telegram_status')
                    ->label('وضعیت تلگرام')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'ارسال شده',
                        'failed' => 'خطا در ارسال',
                        'pending' => 'در حال ارسال',
                        'disabled' => 'ارسال نشده',
                        default => $state,
                    })
                    ->tooltip(function (DatabaseBackup $record): ?string {
                        if ($record->telegram_error) {
                            return 'علت خطا: ' . $record->telegram_error;
                        }
                        if ($record->telegram_sent_at) {
                            return 'زمان ارسال: ' . $record->telegram_sent_at->format('Y-m-d H:i');
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->formatStateUsing(function ($state): string {
                        if (!$state) {
                            return '-';
                        }
                        if (class_exists('IntlDateFormatter')) {
                            try {
                                $fmt = new IntlDateFormatter(
                                    'fa_IR@calendar=persian',
                                    IntlDateFormatter::FULL,
                                    IntlDateFormatter::FULL,
                                    'Asia/Tehran',
                                    IntlDateFormatter::TRADITIONAL,
                                    'yyyy/MM/dd HH:mm'
                                );
                                return $fmt->format($state->timestamp);
                            } catch (\Throwable $e) {
                                // fallback
                            }
                        }
                        return $state->format('Y-m-d H:i');
                    })
                    ->description(fn (DatabaseBackup $record): ?string => $record->created_at ? $record->created_at->format('Y-m-d H:i') : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('database_name')
                    ->label('فیلتر دیتابیس')
                    ->options([
                        'fars_farsifahr' => 'fars_farsifahr (اصلی)',
                        'fars_lara' => 'fars_lara (پنل)',
                        'all' => 'همه دیتابیس‌ها',
                    ]),

                Tables\Filters\SelectFilter::make('telegram_status')
                    ->label('وضعیت تلگرام')
                    ->options([
                        'sent' => 'ارسال شده',
                        'failed' => 'خطا در ارسال',
                        'disabled' => 'ارسال نشده',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('دانلود')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (DatabaseBackup $record) {
                        if (!$record->fileExists()) {
                            Notification::make()
                                ->title('فایل روی سرور موجود نیست')
                                ->danger()
                                ->send();
                            return null;
                        }

                        return response()->download($record->disk_path, $record->filename);
                    }),

                Tables\Actions\Action::make('send_telegram')
                    ->label('ارسال به تلگرام')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('ارسال بکاپ به تلگرام')
                    ->modalDescription(fn (DatabaseBackup $record) => "آیا می‌خواهید فایل {$record->filename} را به کانال تلگرام ارسال کنید؟")
                    ->action(function (DatabaseBackup $record) {
                        $res = BackupService::sendToTelegram($record);
                        if ($res['success']) {
                            Notification::make()
                                ->title('ارسال موفق')
                                ->body($res['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('خطا در ارسال')
                                ->body($res['error'])
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->modalHeading('حذف فایل بکاپ')
                    ->modalDescription('آیا مطمئن هستید؟ این فایل از روی سرور نیز به طور کامل حذف خواهد شد.')
                    ->before(function (DatabaseBackup $record) {
                        if ($record->fileExists()) {
                            File::delete($record->disk_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب‌شده‌ها')
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->fileExists()) {
                                    File::delete($record->disk_path);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDatabaseBackups::route('/'),
        ];
    }
}
