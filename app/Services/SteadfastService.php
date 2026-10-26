<?php

namespace App\Services;

use App\Models\Order\Order;
use App\Models\Order\OrderLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $secretKey;
    protected ?string $webhookToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(Setting::get('steadfast_base_url', 'https://portal.packzy.com/api/v1'), '/');
        $this->apiKey = Setting::get('steadfast_api_key');
        $this->secretKey = Setting::get('steadfast_secret_key');
        $this->webhookToken = Setting::get('steadfast_webhook_token');
    }

    /**
     * Check if API credentials are configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->secretKey);
    }

    /**
     * Get configured API key
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Get configured Secret key
     */
    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * Get configured Webhook token
     */
    public function getWebhookToken(): ?string
    {
        return $this->webhookToken;
    }

    /**
     * Get base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Ping endpoint to check connectivity
     * (Does not require API credentials according to Steadfast docs)
     */
    public function ping(): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)->get("{$this->baseUrl}/ping");
            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'message' => $data['message'] ?? 'pong',
                    'latency' => $latencyMs,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Steadfast server responded with status: ' . $response->status(),
                'latency' => $latencyMs,
                'data' => $response->json() ?? $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Steadfast ping error: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'latency' => null,
                'data' => null,
            ];
        }
    }

    /**
     * Get current merchant balance from Steadfast
     * Endpoint: GET /get_balance
     * Requires: Api-Key and Secret-Key headers
     */
    public function getBalance(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'API Key and Secret Key are not configured yet.',
                'current_balance' => null,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/get_balance");

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 200) {
                return [
                    'success' => true,
                    'status' => 200,
                    'current_balance' => $data['current_balance'] ?? 0,
                    'message' => 'Balance retrieved successfully.',
                    'raw' => $data,
                ];
            }

            $errorMessage = $data['message'] ?? ($data['errors'] ?? 'Failed to retrieve balance from Steadfast.');
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $errorMessage,
                'current_balance' => null,
                'raw' => $data ?? $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Steadfast getBalance error: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Request error: ' . $e->getMessage(),
                'current_balance' => null,
            ];
        }
    }

    /**
     * Create parcel / consignment order in Steadfast
     * Endpoint: POST /create_order
     *
     * @param Order $order
     * @param array $overrides Optional overrides
     * @return array
     */
    public function createOrder(Order $order, array $overrides = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Steadfast API Key or Secret Key is not configured in Admin Settings.',
            ];
        }

        $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);

        $recipientName = trim($overrides['recipient_name'] ?? ($shipping['name'] ?? optional($order->user)->name ?? 'Customer'));
        $rawPhone = $overrides['recipient_phone'] ?? ($shipping['phone'] ?? optional($order->user)->phone ?? '');
        $recipientPhone = $this->formatPhone($rawPhone);

        // Address compilation
        $addressParts = array_filter([
            $shipping['address'] ?? '',
            $shipping['city'] ?? '',
            $shipping['zone'] ?? '',
        ]);
        $recipientAddress = trim($overrides['recipient_address'] ?? implode(', ', $addressParts));

        if (empty($recipientName)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Recipient name is required for Steadfast booking.',
            ];
        }

        if (empty($recipientPhone) || strlen($recipientPhone) !== 11 || !str_starts_with($recipientPhone, '01')) {
            return [
                'success' => false,
                'status' => 422,
                'message' => "Recipient phone number '{$rawPhone}' is invalid. Steadfast requires an 11-digit Bangladeshi mobile number starting with 01.",
            ];
        }

        if (empty($recipientAddress)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Recipient address is required for Steadfast booking.',
            ];
        }

        // COD amount calculation: 0 if already paid, else full order total
        $codAmount = isset($overrides['cod_amount'])
            ? (float) $overrides['cod_amount']
            : ($order->payment_status === 'paid' ? 0.0 : (float) $order->total);
        $codAmount = max(0, round($codAmount));

        $note = trim($overrides['note'] ?? ($order->order_notes ?? ''));

        $payload = [
            'invoice' => (string) $order->order_id,
            'recipient_name' => mb_substr($this->sanitizeText($recipientName), 0, 100),
            'recipient_phone' => $recipientPhone,
            'recipient_address' => mb_substr($this->sanitizeText($recipientAddress), 0, 490),
            'cod_amount' => $codAmount,
        ];

        if (!empty($note)) {
            $payload['note'] = mb_substr($this->sanitizeText($note), 0, 480);
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post("{$this->baseUrl}/create_order", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 200) {
                $consignment = $data['consignment'] ?? [];
                $consignmentId = (string) ($consignment['consignment_id'] ?? '');
                $trackingCode = (string) ($consignment['tracking_code'] ?? '');
                $trackingLink = $consignment['tracking_link'] ?? ($trackingCode ? "https://steadfast.com.bd/t/{$trackingCode}" : null);

                // Update order model
                $existingTracking = is_array($order->tracking) ? $order->tracking : [];
                $existingTracking[] = [
                    'courier' => 'Steadfast',
                    'consignment_id' => $consignmentId,
                    'tracking_code' => $trackingCode,
                    'tracking_link' => $trackingLink,
                    'created_at' => now()->toDateTimeString(),
                ];

                $order->update([
                    'is_added_into_steadfast' => true,
                    'steadfast_consignment_id' => $consignmentId,
                    'steadfast_tracking_code' => $trackingCode,
                    'steadfast_collected_amount' => $codAmount,
                    'tracking' => $existingTracking,
                ]);

                // Create Order Log
                try {
                    OrderLog::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id(),
                        'action' => 'steadfast_parcel_booked',
                        'old_data' => null,
                        'new_data' => [
                            'consignment_id' => $consignmentId,
                            'tracking_code' => $trackingCode,
                            'cod_amount' => $codAmount,
                            'tracking_link' => $trackingLink,
                        ],
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('OrderLog creation error on Steadfast booking: ' . $logEx->getMessage());
                }

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => $data['message'] ?? 'Consignment created successfully.',
                    'consignment' => $consignment,
                    'consignment_id' => $consignmentId,
                    'tracking_code' => $trackingCode,
                    'tracking_link' => $trackingLink,
                ];
            }

            // Handle validation / API errors
            $rawBody = trim(strip_tags($response->body() ?? ''));
            $errorMessage = $data['message'] ?? ($rawBody ?: 'Failed to book parcel on Steadfast.');
            if (!empty($data['errors']) && is_array($data['errors'])) {
                $flattened = [];
                foreach ($data['errors'] as $field => $errs) {
                    $flattened[] = is_array($errs) ? implode(', ', $errs) : $errs;
                }
                $errorMessage = implode(' | ', $flattened);
            }

            Log::error("Steadfast create_order failed for order {$order->order_id}: ", [
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $data,
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $errorMessage,
                'errors' => $data['errors'] ?? null,
                'raw' => $data,
            ];

        } catch (\Exception $e) {
            Log::error("Steadfast create_order exception for order {$order->order_id}: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Connection to Steadfast failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get delivery status by Consignment ID
     * Endpoint: GET /status_by_cid/{consignment_id}
     */
    public function getDeliveryStatusByConsignmentId(string|int $consignmentId): array
    {
        return $this->authenticatedGet("/status_by_cid/{$consignmentId}");
    }

    /**
     * Get delivery status by Invoice / Order ID
     * Endpoint: GET /status_by_invoice/{invoice}
     */
    public function getDeliveryStatusByInvoice(string $invoice): array
    {
        return $this->authenticatedGet("/status_by_invoice/{$invoice}");
    }

    /**
     * Get delivery status by Tracking Code
     * Endpoint: GET /status_by_trackingcode/{tracking_code}
     */
    public function getDeliveryStatusByTrackingCode(string $trackingCode): array
    {
        return $this->authenticatedGet("/status_by_trackingcode/{$trackingCode}");
    }

    /**
     * Get detailed tracking history steps by Invoice
     * Endpoint: GET /trackings_by_invoice/{invoice}
     */
    public function getTrackingsByInvoice(string $invoice): array
    {
        return $this->authenticatedGet("/trackings_by_invoice/{$invoice}");
    }

    /**
     * Check customer fraud score & delivery ratio
     * Endpoint: GET /fraud_check/score/{phone}
     */
    public function checkFraud(string $phone): array
    {
        $cleanPhone = $this->formatPhone($phone);
        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Invalid phone number for fraud check.',
            ];
        }

        return $this->authenticatedGet("/fraud_check/score/{$cleanPhone}");
    }

    /**
     * Helper to perform authenticated GET request to Steadfast API
     */
    protected function authenticatedGet(string $endpoint): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Steadfast API credentials are not configured.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . $endpoint);

            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $data['message'] ?? 'Steadfast API returned error status: ' . $response->status(),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error("Steadfast GET {$endpoint} error: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format Bangladeshi phone number to 11 digits starting with 01
     */
    public function formatPhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '8801')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }
        return $digits;
    }

    /**
     * Sanitize text according to Steadfast requirements:
     * Steadfast strips/replaces { } ; < > $ with a space.
     */
    protected function sanitizeText(?string $text): string
    {
        if (!$text) {
            return '';
        }
        return str_replace(['{', '}', ';', '<', '>', '$'], ' ', $text);
    }
}
