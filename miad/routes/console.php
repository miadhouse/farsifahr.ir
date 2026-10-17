<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// بکاپ اتوماتیک روزانه دیتابیس
Schedule::command('app:backup-database')
    ->dailyAt(BackupService::getSettings()['backup_time'] ?? '03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));
