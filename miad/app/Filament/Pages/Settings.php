<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\DB;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'تنظیمات سیستم';
    protected static ?string $title = 'تنظیمات سیستم';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = [];
        $settingsPath = base_path('../config/settings.json');
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true) ?? [];
        }

        // Load referral settings from database
        $referralNewUser = DB::connection('farsi_fahr2')->table('settings')->where('key', 'referral_reward_new_user')->value('value') ?? 7;
        $referralReferrer = DB::connection('farsi_fahr2')->table('settings')->where('key', 'referral_reward_referrer')->value('value') ?? 14;

        $this->form->fill([
            'session_lifetime' => env('SESSION_LIFETIME', 120),
            'report_reward_days' => env('REPORT_REWARD_DAYS', 10),
            'euro_to_toman_rate' => env('EURO_TO_TOMAN_RATE', 75000),
            
            // Referral settings
            'referral_reward_new_user' => $referralNewUser,
            'referral_reward_referrer' => $referralReferrer,
            
            // Dynamic JSON settings
            'instagram_url' => $settings['instagram_url'] ?? '',
            'telegram_channel_url' => $settings['telegram_channel_url'] ?? '',
            'telegram_support_url' => $settings['telegram_support_url'] ?? '',
            'whatsapp_url' => $settings['whatsapp_url'] ?? '',
            'contact_phone' => $settings['contact_phone'] ?? '',
            'contact_email' => $settings['contact_email'] ?? '',
            'footer_description' => $settings['footer_description'] ?? '',
            'copyright_text' => $settings['copyright_text'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('تنظیمات مالی')
                    ->schema([
                        TextInput::make('euro_to_toman_rate')
                            ->label('نرخ تبدیل یورو به تومان')
                            ->numeric()
                            ->required(),
                    ]),
                
                Section::make('تنظیمات دعوت از دوستان (Referral)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('referral_reward_new_user')
                                ->label('هدیه کاربر جدید (روز)')
                                ->numeric()
                                ->required(),
                            TextInput::make('referral_reward_referrer')
                                ->label('هدیه معرف (روز)')
                                ->numeric()
                                ->required(),
                        ]),
                    ]),

                Section::make('شبکه‌های اجتماعی')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('instagram_url')->label('لینک اینستاگرام')->url(),
                            TextInput::make('telegram_channel_url')->label('لینک کانال تلگرام')->url(),
                            TextInput::make('telegram_support_url')->label('لینک پشتیبانی تلگرام')->url(),
                            TextInput::make('whatsapp_url')->label('لینک/شماره واتس‌اپ')->placeholder('https://wa.me/...'),
                        ]),
                    ]),

                Section::make('اطلاعات تماس و فوتر')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('contact_phone')->label('شماره تماس نمایش در سایت'),
                            TextInput::make('contact_email')->label('ایمیل پشتیبانی')->email(),
                        ]),
                        Textarea::make('footer_description')->label('متن درباره ما (فوتر)')->rows(3),
                        TextInput::make('copyright_text')->label('متن کپی‌رایت')->columnSpanFull(),
                    ]),

                Section::make('تنظیمات فنی')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('session_lifetime')
                                ->label('طول عمر نشست (دقیقه)')
                                ->numeric()
                                ->required(),
                            TextInput::make('report_reward_days')
                                ->label('هدیه گزارش صحیح (روز)')
                                ->numeric()
                                ->required(),
                        ]),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // 1. Save to JSON file
        $settingsPath = base_path('../config/settings.json');
        $jsonSettings = [
            'instagram_url' => $data['instagram_url'],
            'telegram_channel_url' => $data['telegram_channel_url'],
            'telegram_support_url' => $data['telegram_support_url'],
            'whatsapp_url' => $data['whatsapp_url'],
            'contact_phone' => $data['contact_phone'],
            'contact_email' => $data['contact_email'],
            'footer_description' => $data['footer_description'],
            'copyright_text' => $data['copyright_text'],
        ];
        File::put($settingsPath, json_encode($jsonSettings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 2. Save referral settings to database
        DB::connection('farsi_fahr2')->table('settings')->updateOrInsert(
            ['key' => 'referral_reward_new_user'],
            ['value' => $data['referral_reward_new_user'], 'updated_at' => now()]
        );
        DB::connection('farsi_fahr2')->table('settings')->updateOrInsert(
            ['key' => 'referral_reward_referrer'],
            ['value' => $data['referral_reward_referrer'], 'updated_at' => now()]
        );

        // 3. Update Laravel .env for technical settings
        $lifetime = $data['session_lifetime'];
        $rewardDays = $data['report_reward_days'];
        $euroRate = $data['euro_to_toman_rate'];

        $path = base_path('.env');
        if (File::exists($path)) {
            $env = File::get($path);
            $env = preg_replace('/^SESSION_LIFETIME=.*$/m', 'SESSION_LIFETIME=' . $lifetime, $env);
            
            if (preg_match('/^REPORT_REWARD_DAYS=.*$/m', $env)) {
                $env = preg_replace('/^REPORT_REWARD_DAYS=.*$/m', 'REPORT_REWARD_DAYS=' . $rewardDays, $env);
            } else {
                $env .= "\nREPORT_REWARD_DAYS=" . $rewardDays;
            }
            
            if (preg_match('/^EURO_TO_TOMAN_RATE=.*$/m', $env)) {
                $env = preg_replace('/^EURO_TO_TOMAN_RATE=.*$/m', 'EURO_TO_TOMAN_RATE=' . $euroRate, $env);
            } else {
                $env .= "\nEURO_TO_TOMAN_RATE=" . $euroRate;
            }
            
            File::put($path, $env);
        }

        // 3. Update native PHP config.php
        $configPath = base_path('../config/config.php');
        if (File::exists($configPath)) {
            $configContent = File::get($configPath);
            $lifetimeSeconds = $lifetime * 60;
            $configContent = preg_replace('/define\(\'SESSION_LIFETIME\', \d+\);/', 'define(\'SESSION_LIFETIME\', ' . $lifetimeSeconds . ');', $configContent);
            $configContent = preg_replace('/define\(\'EURO_TO_TOMAN_RATE\', \d+\);/', 'define(\'EURO_TO_TOMAN_RATE\', ' . $euroRate . ');', $configContent);
            File::put($configPath, $configContent);
        }

        Notification::make()
            ->title('تنظیمات با موفقیت ذخیره شد')
            ->success()
            ->send();
    }
}
