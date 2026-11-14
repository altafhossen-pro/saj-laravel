<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiService
{
    public const PROVIDER_GEMINI = 'gemini';
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_GROQ = 'groq';
    public const PROVIDER_CLAUDE = 'claude';

    /**
     * Get default active AI provider
     */
    public static function getDefaultProvider(): string
    {
        return Setting::get('default_ai_provider', self::PROVIDER_GEMINI);
    }

    /**
     * Get API key for a specific provider
     */
    public static function getApiKey(string $provider): ?string
    {
        return match ($provider) {
            self::PROVIDER_GEMINI => Setting::get('gemini_api_key', env('GEMINI_API_KEY')),
            self::PROVIDER_OPENAI => Setting::get('openai_api_key', env('OPENAI_API_KEY')),
            self::PROVIDER_GROQ => Setting::get('groq_api_key', env('GROQ_API_KEY')),
            self::PROVIDER_CLAUDE => Setting::get('claude_api_key', env('ANTHROPIC_API_KEY')),
            default => null,
        };
    }

    /**
     * Get selected model for a specific provider
     */
    public static function getModel(string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_GEMINI => Setting::get('gemini_model', 'gemini-2.0-flash'),
            self::PROVIDER_OPENAI => Setting::get('openai_model', 'gpt-4o-mini'),
            self::PROVIDER_GROQ => Setting::get('groq_model', 'llama-3.3-70b-versatile'),
            self::PROVIDER_CLAUDE => Setting::get('claude_model', 'claude-3-5-sonnet-20241022'),
            default => 'default',
        };
    }

    /**
     * Test connection to an AI provider with the given or saved API key
     */
    public static function testConnection(string $provider, ?string $apiKey = null, ?string $model = null): array
    {
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey($provider);
        $model = !empty($model) ? trim($model) : self::getModel($provider);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'provider' => ucfirst($provider),
                'message' => 'API Key is empty. Please enter an API key to test.',
            ];
        }

        $startTime = microtime(true);

        try {
            return match ($provider) {
                self::PROVIDER_GEMINI => self::testGemini($apiKey, $model, $startTime),
                self::PROVIDER_OPENAI => self::testOpenAi($apiKey, $model, $startTime),
                self::PROVIDER_GROQ => self::testGroq($apiKey, $model, $startTime),
                self::PROVIDER_CLAUDE => self::testClaude($apiKey, $model, $startTime),
                default => [
                    'success' => false,
                    'provider' => ucfirst($provider),
                    'message' => "Unsupported AI provider: {$provider}",
                ],
            };
        } catch (Throwable $e) {
            Log::error("AiService testConnection error [{$provider}]: " . $e->getMessage());
            return [
                'success' => false,
                'provider' => ucfirst($provider),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Google Gemini API
     */
    protected static function testGemini(string $apiKey, string $model, float $startTime): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Hello Gemini! Respond in exactly 4 words: "Gemini connection is working!"']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 30,
                ],
            ]);

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->successful()) {
            $data = $response->json();
            $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Connected successfully!';

            return [
                'success' => true,
                'provider' => 'Google Gemini',
                'model' => $model,
                'latency_ms' => $latency,
                'sample_output' => trim($reply),
                'message' => "Connection successful! Gemini ({$model}) responded in {$latency}ms.",
            ];
        }

        $errorMsg = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'provider' => 'Google Gemini',
            'model' => $model,
            'latency_ms' => $latency,
            'message' => "Gemini API error ({$response->status()}): {$errorMsg}",
        ];
    }

    /**
     * Test OpenAI API
     */
    protected static function testOpenAi(string $apiKey, string $model, float $startTime): array
    {
        $response = Http::timeout(15)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Respond with: "OpenAI connection is working!"']
                ],
                'max_tokens' => 20,
            ]);

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->successful()) {
            $reply = $response->json('choices.0.message.content') ?? 'Connected successfully!';
            return [
                'success' => true,
                'provider' => 'OpenAI',
                'model' => $model,
                'latency_ms' => $latency,
                'sample_output' => trim($reply),
                'message' => "Connection successful! OpenAI ({$model}) responded in {$latency}ms.",
            ];
        }

        $errorMsg = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'provider' => 'OpenAI',
            'model' => $model,
            'latency_ms' => $latency,
            'message' => "OpenAI API error ({$response->status()}): {$errorMsg}",
        ];
    }

    /**
     * Test Groq API
     */
    protected static function testGroq(string $apiKey, string $model, float $startTime): array
    {
        $response = Http::timeout(15)
            ->withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Respond with: "Groq connection is working!"']
                ],
                'max_tokens' => 20,
            ]);

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->successful()) {
            $reply = $response->json('choices.0.message.content') ?? 'Connected successfully!';
            return [
                'success' => true,
                'provider' => 'Groq AI',
                'model' => $model,
                'latency_ms' => $latency,
                'sample_output' => trim($reply),
                'message' => "Connection successful! Groq ({$model}) responded in {$latency}ms.",
            ];
        }

        $errorMsg = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'provider' => 'Groq AI',
            'model' => $model,
            'latency_ms' => $latency,
            'message' => "Groq API error ({$response->status()}): {$errorMsg}",
        ];
    }

    /**
     * Test Anthropic Claude API
     */
    protected static function testClaude(string $apiKey, string $model, float $startTime): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 30,
                'messages' => [
                    ['role' => 'user', 'content' => 'Respond with: "Claude connection is working!"']
                ],
            ]);

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->successful()) {
            $reply = $response->json('content.0.text') ?? 'Connected successfully!';
            return [
                'success' => true,
                'provider' => 'Anthropic Claude',
                'model' => $model,
                'latency_ms' => $latency,
                'sample_output' => trim($reply),
                'message' => "Connection successful! Claude ({$model}) responded in {$latency}ms.",
            ];
        }

        $errorMsg = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'provider' => 'Anthropic Claude',
            'model' => $model,
            'latency_ms' => $latency,
            'message' => "Claude API error ({$response->status()}): {$errorMsg}",
        ];
    }

    /**
     * General generation method: uses default or specified provider
     */
    public static function generateContent(string $prompt, ?string $provider = null, ?string $model = null, array $config = []): ?string
    {
        $provider = $provider ?: self::getDefaultProvider();
        $apiKey = self::getApiKey($provider);
        $model = $model ?: self::getModel($provider);

        if (empty($apiKey)) {
            Log::warning("AiService: No API key configured for provider [{$provider}].");
            return null;
        }

        try {
            if ($provider === self::PROVIDER_GEMINI) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout($config['timeout'] ?? 60)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'contents' => [
                            [
                                'parts' => [['text' => $prompt]]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => $config['temperature'] ?? 0.7,
                            'maxOutputTokens' => $config['max_tokens'] ?? 4096,
                        ]
                    ]);

                if ($response->successful()) {
                    return $response->json('candidates.0.content.parts.0.text');
                }

                Log::error("AiService Gemini generation error: " . $response->body());
                return null;
            }

            if ($provider === self::PROVIDER_OPENAI || $provider === self::PROVIDER_GROQ) {
                $url = $provider === self::PROVIDER_GROQ
                    ? 'https://api.groq.com/openai/v1/chat/completions'
                    : 'https://api.openai.com/v1/chat/completions';

                $response = Http::timeout($config['timeout'] ?? 60)
                    ->withToken($apiKey)
                    ->post($url, [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => $config['temperature'] ?? 0.7,
                        'max_tokens' => $config['max_tokens'] ?? 4096,
                    ]);

                if ($response->successful()) {
                    return $response->json('choices.0.message.content');
                }

                Log::error("AiService {$provider} generation error: " . $response->body());
                return null;
            }

            if ($provider === self::PROVIDER_CLAUDE) {
                $response = Http::timeout($config['timeout'] ?? 60)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type' => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model' => $model,
                        'max_tokens' => $config['max_tokens'] ?? 4096,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);

                if ($response->successful()) {
                    return $response->json('content.0.text');
                }

                Log::error("AiService Claude generation error: " . $response->body());
                return null;
            }
        } catch (Throwable $e) {
            Log::error("AiService generateContent exception [{$provider}]: " . $e->getMessage());
        }

        return null;
    }
}
