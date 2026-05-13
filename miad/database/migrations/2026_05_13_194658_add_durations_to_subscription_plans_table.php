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
        Schema::connection('farsi_fahr2')->table('subscription_plans', function (Blueprint $blueprint) {
            $blueprint->json('durations')->nullable()->after('features');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farsi_fahr2')->table('subscription_plans', function (Blueprint $blueprint) {
            $blueprint->dropColumn('durations');
        });
    }
};
