<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('disk_path');
            $table->string('database_name')->default('fars_farsifahr');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status')->default('completed'); // completed, failed
            $table->string('telegram_status')->default('disabled'); // pending, sent, failed, disabled
            $table->timestamp('telegram_sent_at')->nullable();
            $table->text('telegram_error')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
