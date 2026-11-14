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
            self::PROVIDER_GEMINI => Setting::get('gemini_model', 'gemini-3.5-flash-lite'),
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
        set_time_limit(60);
        $cleanModel = str_replace('models/', '', $model);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cleanModel}:generateContent?key={$apiKey}";

        $response = Http::timeout(25)
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_SSL_VERIFYPEER => false,
                ],
                'verify' => false,
            ])
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
                'model' => $cleanModel,
                'latency_ms' => $latency,
                'sample_output' => trim($reply),
                'message' => "Connection successful! Gemini ({$cleanModel}) responded in {$latency}ms.",
            ];
        }

        $errorMsg = $response->json('error.message') ?? $response->body();
        return [
            'success' => false,
            'provider' => 'Google Gemini',
            'model' => $cleanModel,
            'latency_ms' => $latency,
            'message' => "Gemini API error ({$response->status()}): {$errorMsg}",
        ];
    }

    /**
     * Fetch available models from official Google Gemini API
     */
    public static function getGeminiModels(?string $apiKey = null): array
    {
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey(self::PROVIDER_GEMINI);
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini API key is required. Please paste your API key first.',
            ];
        }

        try {
            set_time_limit(60);
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
            $response = Http::timeout(25)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                    'verify' => false,
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $rawModels = $data['models'] ?? [];

                $models = [];
                foreach ($rawModels as $m) {
                    $methods = $m['supportedGenerationMethods'] ?? [];
                    if (in_array('generateContent', $methods)) {
                        $id = str_replace('models/', '', $m['name'] ?? '');
                        $models[] = [
                            'id' => $id,
                            'name' => $m['name'] ?? '',
                            'displayName' => $m['displayName'] ?? $id,
                            'description' => $m['description'] ?? '',
                            'inputTokenLimit' => $m['inputTokenLimit'] ?? null,
                            'outputTokenLimit' => $m['outputTokenLimit'] ?? null,
                        ];
                    }
                }

                // Sort models so flash and newer models come first
                usort($models, function($a, $b) {
                    if (str_contains($a['id'], 'flash') && !str_contains($b['id'], 'flash')) return -1;
                    if (!str_contains($a['id'], 'flash') && str_contains($b['id'], 'flash')) return 1;
                    return strcmp($b['id'], $a['id']);
                });

                return [
                    'success' => true,
                    'total' => count($models),
                    'models' => $models,
                    'message' => 'Found ' . count($models) . ' available models in your Google account.',
                ];
            }

            $errorMsg = $response->json('error.message') ?? $response->body();
            return [
                'success' => false,
                'message' => "Gemini API error ({$response->status()}): {$errorMsg}",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch models: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check limits & quota for a specific Gemini model
     */
    public static function getGeminiLimits(?string $apiKey = null, ?string $model = null): array
    {
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey(self::PROVIDER_GEMINI);
        $model = !empty($model) ? trim($model) : self::getModel(self::PROVIDER_GEMINI);
        $cleanModel = str_replace('models/', '', $model);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini API key is required. Please paste your API key first.',
            ];
        }

        try {
            set_time_limit(60);
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cleanModel}?key={$apiKey}";
            $response = Http::timeout(25)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                    'verify' => false,
                ])
                ->get($url);

            if ($response->successful()) {
                $modelData = $response->json();

                $isPro = str_contains($cleanModel, 'pro');
                $freeLimits = [
                    'rpm' => $isPro ? '2 - 5 RPM' : '15 RPM (Requests/Min)',
                    'rpd' => $isPro ? '50 RPD' : '1,500 RPD (Requests/Day)',
                    'tpm' => $isPro ? '32,000 TPM' : '1,000,000 TPM (Tokens/Min)',
                    'cost' => 'Free ($0.00)',
                ];

                return [
                    'success' => true,
                    'model' => $cleanModel,
                    'displayName' => $modelData['displayName'] ?? $cleanModel,
                    'description' => $modelData['description'] ?? '',
                    'inputTokenLimit' => isset($modelData['inputTokenLimit']) ? number_format($modelData['inputTokenLimit']) . ' tokens' : '1,048,576 tokens',
                    'outputTokenLimit' => isset($modelData['outputTokenLimit']) ? number_format($modelData['outputTokenLimit']) . ' tokens' : '8,192 tokens',
                    'freeTier' => $freeLimits,
                    'message' => "Official limit specifications for {$cleanModel} retrieved successfully.",
                ];
            }

            $errorMsg = $response->json('error.message') ?? $response->body();
            return [
                'success' => false,
                'message' => "Gemini API error ({$response->status()}): {$errorMsg}",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to check limits: ' . $e->getMessage(),
            ];
        }
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
    public static function generateContent(string $prompt, ?string $provider = null, ?string $model = null, array $config = [], ?string $apiKey = null): ?string
    {
        $provider = $provider ?: self::getDefaultProvider();
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey($provider);
        $model = $model ?: self::getModel($provider);

        if (empty($apiKey)) {
            Log::warning("AiService: No API key configured for provider [{$provider}].");
            return null;
        }

        try {
            set_time_limit(180);
            if ($provider === self::PROVIDER_GEMINI) {
                $cleanModel = str_replace('models/', '', $model);
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cleanModel}:generateContent?key={$apiKey}";
                $response = Http::timeout($config['timeout'] ?? 90)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ],
                        'verify' => false,
                    ])
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

                $response = Http::timeout($config['timeout'] ?? 90)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ],
                        'verify' => false,
                    ])
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
                $response = Http::timeout($config['timeout'] ?? 90)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ],
                        'verify' => false,
                    ])
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

    /**
     * Test chat directly with model and return formatted response with latency
     */
    public static function chatTest(string $message, string $provider = 'gemini', ?string $model = null, ?string $apiKey = null): array
    {
        set_time_limit(180);
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey($provider);
        $model = !empty($model) ? trim($model) : self::getModel($provider);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key is empty. Please enter an API key first.',
            ];
        }

        $startTime = microtime(true);

        try {
            $reply = self::generateContent($message, $provider, $model, ['timeout' => 90], $apiKey);
            $latency = round((microtime(true) - $startTime) * 1000);

            if ($reply !== null) {
                return [
                    'success' => true,
                    'provider' => ucfirst($provider),
                    'model' => str_replace('models/', '', $model),
                    'reply' => trim($reply),
                    'latency_ms' => $latency,
                    'message' => "Response received in {$latency}ms",
                ];
            }

            return [
                'success' => false,
                'message' => 'No response returned by the AI provider. Check your API key, rate limits or model permissions.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Chat error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Stream real-time tokens/chunks to a callback function
     * Supports Gemini (SSE), OpenAI (SSE), Groq (SSE), Claude (SSE)
     */
    public static function streamChat(string $message, string $provider = 'gemini', ?string $model = null, ?string $apiKey = null, callable $onChunk = null): void
    {
        set_time_limit(180);
        $apiKey = !empty($apiKey) ? trim($apiKey) : self::getApiKey($provider);
        $model = !empty($model) ? trim($model) : self::getModel($provider);

        if (empty($apiKey)) {
            if ($onChunk) $onChunk("⚠️ API key is empty. Please enter an API key first.");
            return;
        }

        try {
            if ($provider === self::PROVIDER_GEMINI) {
                $cleanModel = str_replace('models/', '', $model);
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cleanModel}:streamGenerateContent?alt=sse&key={$apiKey}";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'contents' => [
                        ['parts' => [['text' => $message]]]
                    ]
                ]));

                $buffer = '';
                $hasContent = false;
                $rawResponse = '';

                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$hasContent, &$rawResponse, $onChunk) {
                    $rawResponse .= $chunk;
                    $buffer .= $chunk;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $jsonStr = substr($line, 6);
                            $data = json_decode($jsonStr, true);
                            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                                if ($text !== '') {
                                    $hasContent = true;
                                    if ($onChunk) $onChunk($text);
                                }
                            }
                        }
                    }
                    return strlen($chunk);
                });

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if (!$hasContent) {
                    if ($httpCode !== 200) {
                        $errJson = json_decode($rawResponse, true);
                        $msg = $errJson['error']['message'] ?? $curlErr ?: "HTTP Error {$httpCode}";
                        if ($onChunk) $onChunk("⚠️ Gemini API error ({$httpCode}): {$msg}");
                    } elseif (!empty($curlErr)) {
                        if ($onChunk) $onChunk("⚠️ Connection error: {$curlErr}");
                    }
                }
                return;
            }

            if ($provider === self::PROVIDER_OPENAI || $provider === self::PROVIDER_GROQ) {
                $endpoint = $provider === self::PROVIDER_GROQ
                    ? 'https://api.groq.com/openai/v1/chat/completions'
                    : 'https://api.openai.com/v1/chat/completions';

                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $message]],
                    'stream' => true,
                ]));

                $buffer = '';
                $hasContent = false;
                $rawResponse = '';

                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$hasContent, &$rawResponse, $onChunk) {
                    $rawResponse .= $chunk;
                    $buffer .= $chunk;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $jsonStr = substr($line, 6);
                            if ($jsonStr === '[DONE]') break;
                            $data = json_decode($jsonStr, true);
                            if (isset($data['choices'][0]['delta']['content'])) {
                                $text = $data['choices'][0]['delta']['content'];
                                if ($text !== '') {
                                    $hasContent = true;
                                    if ($onChunk) $onChunk($text);
                                }
                            }
                        }
                    }
                    return strlen($chunk);
                });

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if (!$hasContent) {
                    if ($httpCode !== 200) {
                        $errJson = json_decode($rawResponse, true);
                        $msg = $errJson['error']['message'] ?? $curlErr ?: "HTTP Error {$httpCode}";
                        if ($onChunk) $onChunk("⚠️ {$provider} error ({$httpCode}): {$msg}");
                    } elseif (!empty($curlErr)) {
                        if ($onChunk) $onChunk("⚠️ Connection error: {$curlErr}");
                    }
                }
                return;
            }

            if ($provider === self::PROVIDER_CLAUDE) {
                $ch = curl_init('https://api.anthropic.com/v1/messages');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                    'anthropic-version: 2023-06-01',
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => $model,
                    'max_tokens' => 4096,
                    'messages' => [['role' => 'user', 'content' => $message]],
                    'stream' => true,
                ]));

                $buffer = '';
                $hasContent = false;
                $rawResponse = '';

                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$hasContent, &$rawResponse, $onChunk) {
                    $rawResponse .= $chunk;
                    $buffer .= $chunk;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $jsonStr = substr($line, 6);
                            $data = json_decode($jsonStr, true);
                            if (isset($data['delta']['text'])) {
                                $text = $data['delta']['text'];
                                if ($text !== '') {
                                    $hasContent = true;
                                    if ($onChunk) $onChunk($text);
                                }
                            }
                        }
                    }
                    return strlen($chunk);
                });

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if (!$hasContent) {
                    if ($httpCode !== 200) {
                        $errJson = json_decode($rawResponse, true);
                        $msg = $errJson['error']['message'] ?? $curlErr ?: "HTTP Error {$httpCode}";
                        if ($onChunk) $onChunk("⚠️ Claude error ({$httpCode}): {$msg}");
                    } elseif (!empty($curlErr)) {
                        if ($onChunk) $onChunk("⚠️ Connection error: {$curlErr}");
                    }
                }
                return;
            }
        } catch (Throwable $e) {
            if ($onChunk) $onChunk("⚠️ Streaming error: " . $e->getMessage());
        }
    }
}
