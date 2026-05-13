<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $plans = DB::connection('farsi_fahr2')->table('subscription_plans')->get();

        foreach ($plans as $plan) {
            $durations = [];

            if ($plan->slug === 'free') {
                continue;
            }

            if ($plan->price_2_weeks > 0) {
                $durations[] = ['label' => '2 هفته', 'days' => 14, 'price' => $plan->price_2_weeks];
            }
            if ($plan->price_1_month > 0) {
                $durations[] = ['label' => '1 ماه', 'days' => 30, 'price' => $plan->price_1_month];
            }
            if ($plan->price_3_months > 0) {
                $durations[] = ['label' => '3 ماه', 'days' => 90, 'price' => $plan->price_3_months];
            }
            if ($plan->price_6_months > 0) {
                $durations[] = ['label' => '6 ماه', 'days' => 180, 'price' => $plan->price_6_months];
            }
            if ($plan->price_1_year > 0) {
                $durations[] = ['label' => '1 سال', 'days' => 365, 'price' => $plan->price_1_year];
            }

            if (!empty($durations)) {
                DB::connection('farsi_fahr2')->table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update(['durations' => json_encode($durations)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse data migration usually, but we could null out durations
        DB::connection('farsi_fahr2')->table('subscription_plans')->update(['durations' => null]);
    }
};
