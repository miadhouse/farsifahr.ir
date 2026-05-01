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
        Schema::connection('farsi_fahr2')->create('question_question_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('question_id');
            $table->unsignedBigInteger('question_tag_id');
            
            $table->foreign('question_tag_id')->references('id')->on('question_tags')->cascadeOnDelete();
            
            $table->unique(['question_id', 'question_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farsi_fahr2')->dropIfExists('question_question_tag');
    }
};
