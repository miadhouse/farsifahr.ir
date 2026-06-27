<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('farsi_fahr2')->create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->string('position'); // top, middle, bottom
            $table->json('target_pages'); // e.g. ["home", "dashboard"]
            $table->string('audience'); // all, members, guests
            $table->string('display_type'); // once, three_times, always, custom, until_date
            $table->integer('custom_views_limit')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farsi_fahr2')->dropIfExists('announcements');
    }
};
