<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class DatabaseBackup extends Model
{
    protected $fillable = [
        'filename',
        'disk_path',
        'database_name',
        'file_size',
        'status',
        'telegram_status',
        'telegram_sent_at',
        'telegram_error',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'telegram_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if physical backup file exists on disk
     */
    public function fileExists(): bool
    {
        return !empty($this->disk_path) && File::exists($this->disk_path);
    }

    /**
     * Get human-readable file size (e.g. 1.25 MB)
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
