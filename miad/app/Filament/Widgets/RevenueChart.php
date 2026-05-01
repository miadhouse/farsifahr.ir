<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'نمودار فروش ماهانه';
    
    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    protected function getData(): array
    {
        // دریافت آمار فروش ۷ ماه اخیر
        $data = Subscription::where('status', 'active')
            ->select(
                DB::raw('SUM(amount_paid) as sum'),
                DB::raw("DATE_FORMAT(created_at, '%M') as month")
            )
            ->groupBy('month')
            ->orderBy('created_at')
            ->limit(7)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'درآمد (واحد)',
                    'data' => $data->pluck('sum')->toArray(),
                    'fill' => 'start',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
