<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use IntlDateFormatter;

class BackupService
{
    public const BACKUP_DIR = '/home/farsifahr.com/secured_backups';

    /**
     * Get backup settings from settings.json
     */
    public static function getSettings(): array
    {
        $settingsPath = base_path('../config/settings.json');
        $settings = [];
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true) ?? [];
        }

        return [
            'backup_daily_enabled' => (bool) ($settings['backup_daily_enabled'] ?? true),
            'backup_time' => $settings['backup_time'] ?? '03:00',
            'backup_database' => $settings['backup_database'] ?? 'fars_farsifahr',
            'backup_telegram_enabled' => (bool) ($settings['backup_telegram_enabled'] ?? true),
            'backup_telegram_channel_id' => $settings['backup_telegram_channel_id'] ?? ($settings['telegram_channel_id'] ?? ''),
            'telegram_bot_token' => $settings['telegram_bot_token'] ?? env('TELEGRAM_BOT_TOKEN', ''),
            'backup_retention_days' => (int) ($settings['backup_retention_days'] ?? 30),
        ];
    }

    /**
     * Save backup settings to settings.json
     */
    public static function saveSettings(array $newSettings): bool
    {
        $settingsPath = base_path('../config/settings.json');
        $settings = [];
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true) ?? [];
        }

        foreach ($newSettings as $k => $v) {
            $settings[$k] = $v;
        }

        return (bool) File::put($settingsPath, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Ensure backup directory exists
     */
    public static function ensureBackupDirectory(): string
    {
        $dir = self::BACKUP_DIR;
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Create a new database backup
     *
     * @param string|null $targetDb 'fars_farsifahr', 'fars_lara', or 'all'
     * @param bool|null $sendToTelegram
     * @return array
     */
    public static function createBackup(?string $targetDb = null, ?bool $sendToTelegram = null): array
    {
        $settings = self::getSettings();
        $targetDb = $targetDb ?: $settings['backup_database'];
        if ($sendToTelegram === null) {
            $sendToTelegram = $settings['backup_telegram_enabled'];
        }

        $backupDir = self::ensureBackupDirectory();
        $dateStr = date('Y-m-d_H-i-s');
        $filename = "backup_{$targetDb}_{$dateStr}.sql.gz";
        $filePath = $backupDir . '/' . $filename;

        // Determine DB credentials
        if ($targetDb === 'fars_lara') {
            $host = config('database.connections.mysql.host', 'localhost');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');
            $dbName = config('database.connections.mysql.database', 'fars_lara');
            $cmd = sprintf(
                'mysqldump --single-transaction --quick --skip-lock-tables -h %s -u %s -p%s %s | gzip -c > %s',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbName),
                escapeshellarg($filePath)
            );
        } elseif ($targetDb === 'all') {
            // Dump both databases
            $host = 'localhost';
            $user = config('database.connections.farsi_fahr2.username');
            $pass = config('database.connections.farsi_fahr2.password');
            $db1 = config('database.connections.farsi_fahr2.database', 'fars_farsifahr');
            $db2 = config('database.connections.mysql.database', 'fars_lara');
            $cmd = sprintf(
                'mysqldump --single-transaction --quick --skip-lock-tables -h %s -u %s -p%s --databases %s %s | gzip -c > %s',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db1),
                escapeshellarg($db2),
                escapeshellarg($filePath)
            );
        } else {
            // Default: fars_farsifahr
            $targetDb = 'fars_farsifahr';
            $host = config('database.connections.farsi_fahr2.host', 'localhost');
            $user = config('database.connections.farsi_fahr2.username');
            $pass = config('database.connections.farsi_fahr2.password');
            $dbName = config('database.connections.farsi_fahr2.database', 'fars_farsifahr');
            $cmd = sprintf(
                'mysqldump --single-transaction --quick --skip-lock-tables -h %s -u %s -p%s %s | gzip -c > %s',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbName),
                escapeshellarg($filePath)
            );
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($filePath) || File::size($filePath) === 0) {
            $errMsg = "خطا در اجرای دستور mysqldump (کد خطا: {$returnCode})";
            Log::error("Backup failed: {$errMsg}");
            return [
                'success' => false,
                'error' => $errMsg,
            ];
        }

        $fileSize = File::size($filePath);

        $backup = DatabaseBackup::create([
            'filename' => $filename,
            'disk_path' => $filePath,
            'database_name' => $targetDb,
            'file_size' => $fileSize,
            'status' => 'completed',
            'telegram_status' => $sendToTelegram ? 'pending' : 'disabled',
        ]);

        $telegramResult = null;
        if ($sendToTelegram) {
            $telegramResult = self::sendToTelegram($backup);
        }

        // Clean backups older than retention days
        self::cleanOldBackups($settings['backup_retention_days']);

        return [
            'success' => true,
            'backup' => $backup,
            'telegram' => $telegramResult,
            'message' => "بکاپ دیتابیس با موفقیت ایجاد شد ({$backup->formatted_size})",
        ];
    }

    /**
     * Send backup file to Telegram
     */
    public static function sendToTelegram(DatabaseBackup $backup): array
    {
        $settings = self::getSettings();
        $token = $settings['telegram_bot_token'];
        $channelId = $settings['backup_telegram_channel_id'];

        if (empty($token) || empty($channelId)) {
            $error = 'توکن ربات تلگرام یا شناسه کانال/چت دریافت‌کننده تنظیم نشده است.';
            $backup->update([
                'telegram_status' => 'failed',
                'telegram_error' => $error,
            ]);
            return ['success' => false, 'error' => $error];
        }

        if (!$backup->fileExists()) {
            $error = 'فایل بکاپ روی سرور یافت نشد.';
            $backup->update([
                'telegram_status' => 'failed',
                'telegram_error' => $error,
            ]);
            return ['success' => false, 'error' => $error];
        }

        // Format dates
        $gregorian = $backup->created_at ? $backup->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $persian = $gregorian;
        if (class_exists('IntlDateFormatter')) {
            try {
                $fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
                $timestamp = $backup->created_at ? $backup->created_at->timestamp : time();
                $persian = $fmt->format($timestamp);
            } catch (\Throwable $e) {
                // fallback
            }
        }

        $caption = "📦 <b>پشتیبان خودکار دیتابیس FarsiFahr</b>\n\n"
            . "🗄️ <b>دیتابیس:</b> <code>{$backup->database_name}</code>\n"
            . "💾 <b>حجم فایل:</b> <b>{$backup->formatted_size}</b>\n"
            . "📁 <b>نام فایل:</b> <code>{$backup->filename}</code>\n"
            . "📅 <b>تاریخ شمسی:</b> {$persian}\n"
            . "🕒 <b>تاریخ میلادی:</b> {$gregorian}\n"
            . "🌐 <b>دامنه:</b> farsifahr.com\n\n"
            . "🔒 <i>نسخه پشتیبان به صورت فشرده (gzip) ارسال شده است.</i>";

        $url = "https://api.telegram.org/bot{$token}/sendDocument";

        $cFile = new \CURLFile($backup->disk_path, 'application/gzip', $backup->filename);
        $postData = [
            'chat_id' => $channelId,
            'document' => $cFile,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $error = "خطا در ارتباط با سرور تلگرام: {$curlErr}";
            $backup->update([
                'telegram_status' => 'failed',
                'telegram_error' => $error,
            ]);
            Log::error("Telegram backup upload failed: {$error}");
            return ['success' => false, 'error' => $error];
        }

        $res = json_decode($response, true);
        if ($httpCode === 200 && isset($res['ok']) && $res['ok'] === true) {
            $backup->update([
                'telegram_status' => 'sent',
                'telegram_sent_at' => now(),
                'telegram_error' => null,
            ]);
            return ['success' => true, 'message' => 'با موفقیت به تلگرام ارسال شد.'];
        }

        $error = $res['description'] ?? "کد پاسخ تلگرام: {$httpCode}";
        $backup->update([
            'telegram_status' => 'failed',
            'telegram_error' => $error,
        ]);
        Log::error("Telegram backup upload failed: {$error}");
        return ['success' => false, 'error' => $error];
    }

    /**
     * Synchronize backup files existing on disk into database_backups table
     */
    public static function syncFromDisk(): int
    {
        $dir = self::ensureBackupDirectory();
        $files = File::glob($dir . '/backup_*.sql*');
        $synced = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $size = File::size($filePath);
            $modified = File::lastModified($filePath);

            // Determine db name from filename: backup_{dbname}_{date}.sql...
            $dbName = 'fars_farsifahr';
            if (preg_match('/^backup_([a-zA-Z0-9_-]+)_[0-9]{4}-[0-9]{2}-[0-9]{2}/', $filename, $matches)) {
                $dbName = $matches[1];
            }

            $exists = DatabaseBackup::where('filename', $filename)->first();
            if (!$exists) {
                DatabaseBackup::create([
                    'filename' => $filename,
                    'disk_path' => $filePath,
                    'database_name' => $dbName,
                    'file_size' => $size,
                    'status' => 'completed',
                    'telegram_status' => 'disabled',
                    'created_at' => date('Y-m-d H:i:s', $modified),
                    'updated_at' => date('Y-m-d H:i:s', $modified),
                ]);
                $synced++;
            } else {
                // Update file size if changed
                if ($exists->file_size !== $size || $exists->disk_path !== $filePath) {
                    $exists->update([
                        'file_size' => $size,
                        'disk_path' => $filePath,
                    ]);
                }
            }
        }

        // Clean records whose files were deleted manually
        $allRecords = DatabaseBackup::all();
        foreach ($allRecords as $record) {
            if (!$record->fileExists()) {
                $record->delete();
            }
        }

        return $synced;
    }

    /**
     * Delete a backup file from disk and database
     */
    public static function deleteBackup(DatabaseBackup $backup): bool
    {
        if ($backup->fileExists()) {
            File::delete($backup->disk_path);
        }
        return (bool) $backup->delete();
    }

    /**
     * Clean backups older than given days
     */
    public static function cleanOldBackups(int $days = 30): int
    {
        if ($days <= 0) {
            return 0;
        }

        $threshold = now()->subDays($days);
        $oldBackups = DatabaseBackup::where('created_at', '<', $threshold)->get();
        $deleted = 0;

        foreach ($oldBackups as $backup) {
            self::deleteBackup($backup);
            $deleted++;
        }

        return $deleted;
    }
}
