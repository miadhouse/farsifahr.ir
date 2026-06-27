<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('farsi_fahr2')->create('user_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // match int(11) in users table
            $table->unsignedBigInteger('announcement_id');
            $table->timestamp('read_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
            $table->unique(['user_id', 'announcement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farsi_fahr2')->dropIfExists('user_announcement_reads');
    }
};
