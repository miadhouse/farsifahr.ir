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
        Schema::connection('farsi_fahr2')->create('posts', function (Blueprint $post) {
            $post->id();
            $post->string('title');
            $post->string('slug')->unique();
            $post->text('content');
            $post->string('image')->nullable();
            $post->string('author_name')->default('Admin');
            $post->timestamp('published_at')->nullable();
            $post->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farsi_fahr2')->dropIfExists('posts');
    }
};
