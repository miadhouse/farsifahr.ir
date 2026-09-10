<x-filament-panels::page>
    <div wire:poll.5s>
        <!-- Realtime status indicator -->
        <div class="flex items-center justify-between mb-4 p-4 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">بروزرسانی زنده (هر ۵ ثانیه)</span>
            </div>
            <div class="text-xs text-gray-500">
                آخرین بروزرسانی: {{ now()->format('H:i:s') }}
            </div>
        </div>

        @php
            $stats = $this->getOnlineStats();
            $onlineList = $this->getOnlineUsers();
            $loggedUsers = $onlineList->filter(fn($u) => $u['is_logged_in']);
            $guests = $onlineList->filter(fn($u) => !$u['is_logged_in']);
        @endphp

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Online Card -->
            <div class="flex items-center p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm gap-4">
                <div class="p-3 bg-primary-50 dark:bg-primary-950/30 text-primary-600 dark:text-primary-400 rounded-lg">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94-3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کل افراد آنلاین</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $stats['total'] }} نفر</p>
                </div>
            </div>

            <!-- Logged Users Card -->
            <div class="flex items-center p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm gap-4">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کاربران عضو شده</p>
                    <p class="text-3xl font-bold mt-1 text-emerald-600 dark:text-emerald-500">{{ $stats['users'] }} نفر</p>
                </div>
            </div>

            <!-- Guests Card -->
            <div class="flex items-center p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm gap-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-lg">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 0a9.001 9.001 0 0 0-8.716 6.747M12 3a9.001 9.001 0 0 1 8.716 6.747" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کاربران مهمان</p>
                    <p class="text-3xl font-bold mt-1 text-amber-600 dark:text-amber-500">{{ $stats['guests'] }} نفر</p>
                </div>
            </div>
        </div>

        <!-- Logged-in Users Table -->
        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">کاربران عضو شده آنلاین ({{ $loggedUsers->count() }})</h3>
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                @if($loggedUsers->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        در حال حاضر هیچ کاربر عضو شده‌ای آنلاین نیست.
                    </div>
                @else
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                            <tr>
                                <th class="p-4">نام کاربر</th>
                                <th class="p-4">ایمیل</th>
                                <th class="p-4">آی‌پی و موقعیت</th>
                                <th class="p-4">صفحه فعلی</th>
                                <th class="p-4">سیستم عامل / مرورگر</th>
                                <th class="p-4">آخرین فعالیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                            @foreach($loggedUsers as $user)
                                <tr>
                                    <td class="p-4 font-semibold text-gray-900 dark:text-white">{{ $user['name'] }}</td>
                                    <td class="p-4">{{ $user['email'] }}</td>
                                    <td class="p-4">
                                        <div class="font-mono text-xs">{{ $user['ip_address'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $user['location'] }}</div>
                                    </td>
                                    <td class="p-4 text-left">
                                        <a href="{{ $user['page_url'] }}" target="_blank" class="text-primary-600 hover:underline max-w-xs truncate block text-left font-mono text-xs">
                                            {{ $user['page_url'] ?: 'مشخص نشده' }}
                                        </a>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-xs">
                                            {{ $user['os'] }} / {{ $user['browser'] }}
                                        </span>
                                    </td>
                                    <td class="p-4">{{ $user['last_active_text'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Guests Table -->
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">بازدیدکنندگان مهمان آنلاین ({{ $guests->count() }})</h3>
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                @if($guests->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        در حال حاضر هیچ بازدیدکننده مهمانی آنلاین نیست.
                    </div>
                @else
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                            <tr>
                                <th class="p-4">شناسه مهمان</th>
                                <th class="p-4">آی‌پی و موقعیت</th>
                                <th class="p-4">صفحه فعلی</th>
                                <th class="p-4">سیستم عامل / مرورگر</th>
                                <th class="p-4">آخرین فعالیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                            @foreach($guests as $guest)
                                <tr>
                                    <td class="p-4 font-semibold text-gray-900 dark:text-white">مهمان #{{ $guest['id'] }}</td>
                                    <td class="p-4">
                                        <div class="font-mono text-xs">{{ $guest['ip_address'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $guest['location'] }}</div>
                                    </td>
                                    <td class="p-4 text-left">
                                        <a href="{{ $guest['page_url'] }}" target="_blank" class="text-primary-600 hover:underline max-w-xs truncate block text-left font-mono text-xs">
                                            {{ $guest['page_url'] ?: 'مشخص نشده' }}
                                        </a>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-xs">
                                            {{ $guest['os'] }} / {{ $guest['browser'] }}
                                        </span>
                                    </td>
                                    <td class="p-4">{{ $guest['last_active_text'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
