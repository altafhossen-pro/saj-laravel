<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $chatIds;

    public function __construct()
    {
        $this->botToken = Setting::get('telegram_bot_token');
        $this->chatIds = Setting::get('telegram_chat_ids');
    }

    /**
     * Check if telegram bot is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatIds);
    }

    /**
     * Get the Bot Token
     */
    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    /**
     * Get array of configured Chat IDs
     * (Supports comma-separated or newline-separated chat IDs)
     */
    public function getChatIdList(): array
    {
        if (empty($this->chatIds)) {
            return [];
        }

        $ids = preg_split('/[\r\n,]+/', $this->chatIds);
        return array_values(array_filter(array_map('trim', $ids)));
    }

    /**
     * Get Bot Info (Test token validity with getMe)
     */
    public function getMe(?string $token = null): array
    {
        $token = $token ?: $this->botToken;

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Telegram Bot Token is missing.',
            ];
        }

        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");
            $latencyMs = round((microtime(true) - $startTime) * 1000);

            $data = $response->json();

            if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                return [
                    'success' => true,
                    'latency' => $latencyMs,
                    'bot' => $data['result'] ?? [],
                    'message' => 'Bot connected successfully: @' . ($data['result']['username'] ?? ''),
                ];
            }

            return [
                'success' => false,
                'latency' => $latencyMs,
                'message' => $data['description'] ?? 'Failed to connect to Telegram Bot API.',
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getMe error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a message to configured chat IDs or a specific chat ID
     *
     * @param string $text
     * @param string|null $chatId If null, sends to all configured chat IDs
     * @param string $parseMode Default 'HTML'
     * @return array
     */
    public function sendMessage(string $text, ?string $chatId = null, string $parseMode = 'HTML'): array
    {
        $token = $this->botToken;

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Telegram Bot Token is not configured.',
            ];
        }

        $targets = $chatId ? [$chatId] : $this->getChatIdList();

        if (empty($targets)) {
            return [
                'success' => false,
                'message' => 'No Telegram Chat ID specified or configured.',
            ];
        }

        $results = [];
        $hasFailure = false;

        foreach ($targets as $targetId) {
            try {
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $targetId,
                    'text' => $text,
                    'parse_mode' => $parseMode,
                    'disable_web_page_preview' => true,
                ]);

                $data = $response->json();

                if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                    $results[$targetId] = [
                        'success' => true,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ];
                } else {
                    $hasFailure = true;
                    $results[$targetId] = [
                        'success' => false,
                        'error' => $data['description'] ?? 'Unknown Telegram error',
                    ];
                    Log::warning("Telegram sendMessage failed for chat {$targetId}: " . ($data['description'] ?? ''));
                }
            } catch (\Exception $e) {
                $hasFailure = true;
                $results[$targetId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                Log::error("Telegram sendMessage exception for chat {$targetId}: " . $e->getMessage());
            }
        }

        return [
            'success' => !$hasFailure,
            'message' => !$hasFailure ? 'Message sent successfully to all recipients.' : 'Message sent with some errors.',
            'results' => $results,
        ];
    }
}
