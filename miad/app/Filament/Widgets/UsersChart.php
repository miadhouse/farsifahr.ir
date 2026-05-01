<?php

namespace App\Filament\Widgets;

use App\Models\SiteUser;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UsersChart extends ChartWidget
{
    protected static ?string $heading = 'رشد کاربران (جدید)';
    
    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $data = SiteUser::select(
                DB::raw('COUNT(*) as count'),
                DB::raw("DATE_FORMAT(created_at, '%M') as month")
            )
            ->groupBy('month')
            ->orderBy('created_at')
            ->limit(7)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'کاربران جدید',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgb(255, 99, 132)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
