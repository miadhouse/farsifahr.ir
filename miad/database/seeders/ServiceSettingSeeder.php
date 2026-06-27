<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('farsi_fahr2')->table('service_settings')->updateOrInsert(
            ['service_key' => 'translation'],
            [
                'title' => 'ترجمه رسمی گواهینامه',
                'description' => 'ترجمه رسمی گواهینامه رانندگی ایرانی شما به آلمانی توسط مترجم رسمی قسم‌خورده در آلمان با تاییدیه ADAC با بالاترین سرعت و دقت.',
                'price' => 50.00,
                'whatsapp_message' => "سلام، من درخواست ترجمه گواهینامه با نام :name :family را ثبت کرده‌ام. لطفا برای مراحل بعدی راهنمایی کنید.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::connection('farsi_fahr2')->table('service_settings')->updateOrInsert(
            ['service_key' => 'eyetest'],
            [
                'title' => 'نوبت تست چشم‌پزشکی',
                'description' => 'یکی از پیش‌نیازهای دریافت گواهینامه رانندگی در آلمان، تست چشم‌پزشکی (Sehtest) است. ما برای شما به سرعت و به صورت کاملا رایگان نوبت می‌گیریم.',
                'price' => 0.00,
                'whatsapp_message' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::connection('farsi_fahr2')->table('service_settings')->updateOrInsert(
            ['service_key' => 'firstaid'],
            [
                'title' => 'کورس کمک‌های اولیه (Erste Hilfe)',
                'description' => 'شرکت در دوره کمک‌های اولیه برای گرفتن گواهینامه آلمانی اجباری است. ما نوبت کورس مناسب را برای شما در سریع‌ترین زمان رزرو می‌کنیم.',
                'price' => 0.00,
                'whatsapp_message' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
