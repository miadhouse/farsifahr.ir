<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['app.url' => 'https://imiad.online']);
        config(['filesystems.disks.public.url' => 'https://imiad.online/storage']);

        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => Blade::render('<link rel="stylesheet" href="/assets/css/font-ir.css">'),
        );

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => Blade::render('
                <style>
                    body, * {
                        font-family: "IRANSans", "Tahoma", sans-serif !important;
                    }
                </style>
            '),
        );
    }
}
