<?php

namespace App\Filament\Widgets;

use App\Models\SiteUser;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalUsers = SiteUser::count();
        $totalRevenue = Subscription::where('status', 'active')->sum('amount_paid');
        $pendingOrders = Subscription::where('status', 'pending')->count();

        return [
            Stat::make('کل کاربران سایت', number_format($totalUsers))
                ->description('تعداد کل اعضا')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('مجموع درآمد', number_format($totalRevenue) . ' واحد')
                ->description('پرداختی‌های موفق')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart([10, 15, 8, 12, 19, 14, 25]),

            Stat::make('سفارش‌های در انتظار', $pendingOrders)
                ->description('نیاز به تایید')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([3, 5, 2, 8, 4, 11, 6]),
        ];
    }
}
