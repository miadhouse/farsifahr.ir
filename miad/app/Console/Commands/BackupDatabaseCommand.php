<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database 
                            {--database= : Target database to backup (fars_farsifahr, fars_lara, all)}
                            {--telegram : Send backup file to Telegram}
                            {--no-telegram : Do not send to Telegram}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup and optionally send to Telegram';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = BackupService::getSettings();

        // Check if daily backup is enabled (unless command was invoked manually with specific options)
        if (!$settings['backup_daily_enabled'] && !$this->option('database') && !$this->option('telegram')) {
            $this->warn('پشتیبان‌گیری خودکار روزانه در تنظیمات غیرفعال است.');
            return self::SUCCESS;
        }

        $targetDb = $this->option('database') ?: $settings['backup_database'];

        $sendTelegram = null;
        if ($this->option('telegram')) {
            $sendTelegram = true;
        } elseif ($this->option('no-telegram')) {
            $sendTelegram = false;
        } else {
            $sendTelegram = (bool) $settings['backup_telegram_enabled'];
        }

        $this->info("در حال ایجاد بکاپ از دیتابیس {$targetDb}...");

        $result = BackupService::createBackup($targetDb, $sendTelegram);

        if (!$result['success']) {
            $this->error('خطا در ایجاد بکاپ: ' . ($result['error'] ?? 'نامشخص'));
            return self::FAILURE;
        }

        $backup = $result['backup'];
        $this->info("بکاپ با موفقیت ایجاد شد: {$backup->filename} ({$backup->formatted_size})");

        if ($sendTelegram) {
            $tg = $result['telegram'] ?? null;
            if ($tg && $tg['success']) {
                $this->info('فایل بکاپ با موفقیت به تلگرام ارسال شد.');
            } else {
                $this->warn('خطا در ارسال به تلگرام: ' . ($tg['error'] ?? 'خطای نامشخص'));
            }
        }

        return self::SUCCESS;
    }
}
