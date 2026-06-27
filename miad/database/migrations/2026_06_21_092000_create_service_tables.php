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
        $connection = 'farsi_fahr2';

        Schema::connection($connection)->create('service_settings', function (Blueprint $table) {
            $table->id();
            $table->string('service_key')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->text('whatsapp_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('license_translation_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // changed to match int(11) of users table
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('postal_code');
            $table->string('city');
            $table->string('street');
            $table->string('house_number');
            $table->string('additional_address')->nullable();
            $table->string('front_image_path');
            $table->string('back_image_path');
            $table->string('status')->default('pending_payment'); // pending_payment, pending_review, processing, shipped, completed, cancelled
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('payment_contact_method')->nullable(); // whatsapp, telegram
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::connection($connection)->create('eye_test_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // changed to match int(11) of users table
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('postal_code');
            $table->string('city');
            $table->string('street');
            $table->string('house_number');
            $table->string('additional_address')->nullable();
            $table->string('status')->default('pending'); // pending, approved, completed, cancelled
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::connection($connection)->create('first_aid_course_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // changed to match int(11) of users table
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('postal_code');
            $table->string('city');
            $table->string('street');
            $table->string('house_number');
            $table->string('additional_address')->nullable();
            $table->string('status')->default('pending'); // pending, approved, completed, cancelled
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'farsi_fahr2';

        Schema::connection($connection)->dropIfExists('first_aid_course_appointment_requests');
        Schema::connection($connection)->dropIfExists('eye_test_appointment_requests');
        Schema::connection($connection)->dropIfExists('license_translation_requests');
        Schema::connection($connection)->dropIfExists('service_settings');
    }
};
