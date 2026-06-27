<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a blog post to the configured Telegram channel.
     *
     * @param Post $post
     * @return bool
     */
    public static function sendPostToChannel(Post $post): bool
    {
        $token = config('services.telegram.bot_token')
            ?: (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN ? TELEGRAM_BOT_TOKEN : env('TELEGRAM_BOT_TOKEN'));
        $channelId = config('services.telegram.channel_id')
            ?: (defined('TELEGRAM_CHANNEL_ID') && TELEGRAM_CHANNEL_ID ? TELEGRAM_CHANNEL_ID : env('TELEGRAM_CHANNEL_ID'));

        if (!$token || !$channelId) {
            Log::warning('Telegram bot token or channel ID is not configured in environment variables.');
            return false;
        }

        // Format caption
        $cleanContent = strip_tags($post->content);
        $cleanContent = html_entity_decode($cleanContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Replace multiple spaces/newlines with a single space
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);
        $cleanContent = trim($cleanContent);

        // Limit the caption to not exceed Telegram's caption limit (1024 chars for photos)
        // Leaving room for title and URL
        $excerpt = mb_strlen($cleanContent) > 300
            ? mb_substr($cleanContent, 0, 300) . '...'
            : $cleanContent;

        $siteUrl = 'https://farsifahr.com';
        $postUrl = $siteUrl . '/blog-details.php?id=' . $post->id;

        $caption = "📣 <b>" . htmlspecialchars($post->title) . "</b>\n\n";
        if (!empty($excerpt)) {
            $caption .= "📝 " . htmlspecialchars($excerpt) . "\n\n";
        }
        $caption .= "🔗 <b>مشاهده کامل مطلب در سایت:</b>\n" . $postUrl;

        $imagePath = $post->image ? storage_path('app/public/' . $post->image) : null;

        if ($imagePath && file_exists($imagePath)) {
            $url = "https://api.telegram.org/bot{$token}/sendPhoto";
            $data = [
                'chat_id' => $channelId,
                'photo' => new \CURLFile($imagePath),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ];
        } else {
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $data = [
                'chat_id' => $channelId,
                'text' => $caption,
                'parse_mode' => 'HTML',
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // multipart/form-data for CURLFile
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // in case local SSL cert issues

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('Telegram auto-posting failed.', [
                'http_code' => $httpCode,
                'curl_error' => $curlErr,
                'response' => json_decode($response, true) ?: $response,
                'post_id' => $post->id
            ]);
            return false;
        }

        return true;
    }
}
