<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'تنظیمات سیستم';
    protected static ?string $title = 'تنظیمات سیستم';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'session_lifetime' => env('SESSION_LIFETIME', 120),
            'report_reward_days' => env('REPORT_REWARD_DAYS', 10),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('تنظیمات نشست (Session)')
                    ->schema([
                        TextInput::make('session_lifetime')
                            ->label('طول عمر نشست (دقیقه)')
                            ->numeric()
                            ->required()
                            ->helperText('مدت زمانی که کاربر در صورت عدم فعالیت لاگین باقی می‌ماند.'),
                    ]),
                Section::make('تنظیمات گزارشات')
                    ->schema([
                        TextInput::make('report_reward_days')
                            ->label('هدیه گزارش صحیح (روز اشتراک)')
                            ->numeric()
                            ->required()
                            ->helperText('تعداد روز اشتراک VIP که به عنوان پاداش برای گزارش صحیح به کاربر داده می‌شود.'),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $lifetime = $data['session_lifetime'];
        $rewardDays = $data['report_reward_days'];

        $path = base_path('.env');
        if (File::exists($path)) {
            $env = File::get($path);
            $env = preg_replace('/^SESSION_LIFETIME=.*$/m', 'SESSION_LIFETIME=' . $lifetime, $env);
            
            if (preg_match('/^REPORT_REWARD_DAYS=.*$/m', $env)) {
                $env = preg_replace('/^REPORT_REWARD_DAYS=.*$/m', 'REPORT_REWARD_DAYS=' . $rewardDays, $env);
            } else {
                $env .= "\nREPORT_REWARD_DAYS=" . $rewardDays;
            }
            
            File::put($path, $env);

            Notification::make()
                ->title('تنظیمات با موفقیت ذخیره شد')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('خطا در یافتن فایل تنظیمات')
                ->danger()
                ->send();
        }
    }
}
