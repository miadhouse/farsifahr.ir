<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OnlineUsers extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'کاربران آنلاین';
    protected static ?string $title = 'رصد لحظه‌ای کاربران آنلاین';

    protected static string $view = 'filament.pages.online-users';

    public function getOnlineStats()
    {
        $onlineThreshold = now()->subMinutes(3);

        $total = DB::connection('farsi_fahr2')
            ->table('chat_sessions')
            ->where('last_seen', '>=', $onlineThreshold)
            ->count();

        $users = DB::connection('farsi_fahr2')
            ->table('chat_sessions')
            ->where('last_seen', '>=', $onlineThreshold)
            ->whereNotNull('user_id')
            ->count();

        $guests = DB::connection('farsi_fahr2')
            ->table('chat_sessions')
            ->where('last_seen', '>=', $onlineThreshold)
            ->whereNull('user_id')
            ->count();

        return [
            'total' => $total,
            'users' => $users,
            'guests' => $guests,
        ];
    }

    public function getOnlineUsers()
    {
        $onlineThreshold = now()->subMinutes(3);

        $sessions = DB::connection('farsi_fahr2')
            ->table('chat_sessions as cs')
            ->leftJoin('users as u', 'cs.user_id', '=', 'u.id')
            ->select(
                'cs.id',
                'cs.user_id',
                'cs.guest_name',
                'cs.guest_email',
                'cs.ip_address',
                'cs.user_agent',
                'cs.page_url',
                'cs.last_seen',
                'u.name as registered_name',
                'u.email as registered_email'
            )
            ->where('cs.last_seen', '>=', $onlineThreshold)
            ->orderBy('cs.last_seen', 'desc')
            ->get();

        return $sessions->map(function ($session) {
            $isLoggedIn = !empty($session->user_id);
            $name = $isLoggedIn ? ($session->registered_name ?? 'کاربر ثبت‌نام شده') : ($session->guest_name ?? 'مهمان');
            $email = $isLoggedIn ? $session->registered_email : ($session->guest_email ?? 'بدون ایمیل');
            
            // OS and Browser parsing
            $userAgent = $session->user_agent;
            $browser = 'Unknown';
            $os = 'Unknown';
            
            if (preg_match('/android/i', $userAgent)) {
                $os = 'Android';
            } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
                $os = 'iOS';
            } elseif (preg_match('/windows|win32/i', $userAgent)) {
                $os = 'Windows';
            } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
                $os = 'macOS';
            } elseif (preg_match('/linux/i', $userAgent)) {
                $os = 'Linux';
            }

            if (preg_match('/chrome/i', $userAgent)) {
                $browser = 'Chrome';
            } elseif (preg_match('/safari/i', $userAgent)) {
                $browser = 'Safari';
            } elseif (preg_match('/firefox/i', $userAgent)) {
                $browser = 'Firefox';
            } elseif (preg_match('/edge/i', $userAgent)) {
                $browser = 'Edge';
            } elseif (preg_match('/opera|opr/i', $userAgent)) {
                $browser = 'Opera';
            }

            // Get cached location
            $location = $this->getIpLocation($session->ip_address);

            // Last active in seconds
            $lastActiveDiff = now()->diffInSeconds(\Carbon\Carbon::parse($session->last_seen));
            if ($lastActiveDiff < 60) {
                $lastActiveText = 'کمتر از یک دقیقه پیش';
            } else {
                $lastActiveText = round($lastActiveDiff / 60) . ' دقیقه پیش';
            }

            return [
                'id' => $session->id,
                'is_logged_in' => $isLoggedIn,
                'name' => $name,
                'email' => $email,
                'ip_address' => $session->ip_address,
                'page_url' => $session->page_url,
                'browser' => $browser,
                'os' => $os,
                'location' => $location,
                'last_active_text' => $lastActiveText,
            ];
        });
    }

    public function getIpLocation($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') return 'Localhost';
        
        return cache()->remember('ip_loc_' . $ip, 86400 * 7, function () use ($ip) {
            try {
                $response = Http::timeout(2)
                    ->get("http://ip-api.com/json/{$ip}?fields=status,message,country,city");
                if ($response->successful()) {
                    $data = $response->json();
                    if ($data && ($data['status'] ?? '') === 'success') {
                        return ($data['city'] ?? 'Unknown') . ', ' . ($data['country'] ?? 'Unknown');
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
            return 'Unknown';
        });
    }
}
