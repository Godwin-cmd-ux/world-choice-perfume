<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl = 'https://pay.pesapal.com/v3/api';

    public function __construct(?string $consumerKey = null, ?string $consumerSecret = null)
    {
        $this->consumerKey = $consumerKey ?? (string) config('services.pesapal.consumer_key', '');
        $this->consumerSecret = $consumerSecret ?? (string) config('services.pesapal.consumer_secret', '');
    }

    /**
     * Get an OAuth access token from PesaPal (v3).
     */
    public function getToken(): ?string
    {
        try {
            $response = Http::asJson()->timeout(30)->post($this->baseUrl . '/Auth/RequestToken', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if ($response->successful() && $response->json('token')) {
                return $response->json('token');
            }

            Log::error('PesaPal token request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PesaPal token request error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Register (or re-register) an IPN (Instant Payment Notification) URL.
     *
     * @return array{ipn_id: string, url: string}|null
     */
    public function registerIpn(?string $url = null, string $type = 'POST'): ?array
    {
        $token = $this->getToken();

        if (!$token) {
            return null;
        }

        $url = $url ?? rtrim((string) config('app.url'), '/') . '/pesapal/ipn';

        try {
            $response = Http::withToken($token)->asJson()->timeout(30)->post($this->baseUrl . '/URLSetup/RegisterIPN', [
                'url' => $url,
                'ipn_notification_type' => $type,
            ]);

            if ($response->successful() && $response->json('ipn_id')) {
                return [
                    'ipn_id' => $response->json('ipn_id'),
                    'url' => $response->json('url') ?? $url,
                ];
            }

            Log::error('PesaPal IPN registration failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PesaPal IPN registration error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Submit an order request to PesaPal and get the redirect URL.
     *
     * @param array $params id, amount, currency, description, email, phone,
     *                      first_name, last_name, country_code, callback_url,
     *                      notification_id
     * @return array{success: bool, message?: string, order_tracking_id?: string, redirect_url?: string, merchant_reference?: string}
     */
    public function initiatePayment(array $params): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Failed to get PesaPal token.'];
        }

        $notificationId = $params['notification_id'] ?? null;

        if (!$notificationId) {
            $ipn = $this->registerIpn();

            if (!$ipn) {
                return ['success' => false, 'message' => 'Failed to register PesaPal IPN URL.'];
            }

            $notificationId = $ipn['ipn_id'];
        }

        $fullName = trim((string) ($params['first_name'] ?? ''));
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0] ?? $fullName;
        $lastName = $nameParts[1] ?? 'Customer';

        try {
            $response = Http::withToken($token)->asJson()->timeout(30)->post($this->baseUrl . '/Transactions/SubmitOrderRequest', [
                'id' => (string) ($params['id'] ?? uniqid('PAY-')),
                'currency' => $params['currency'] ?? 'TZS',
                'amount' => (float) ($params['amount'] ?? 0),
                'description' => substr($params['description'] ?? 'Order payment', 0, 200),
                'callback_url' => $params['callback_url'] ?? route('pesapal.callback'),
                'notification_id' => $notificationId,
                'billing_address' => [
                    'email_address' => $params['email'] ?? '',
                    'phone_number' => $params['phone'] ?? '',
                    'country_code' => $params['country_code'] ?? 'TZ',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'line_1' => $params['line_1'] ?? '',
                ],
            ]);

            if ($response->successful() && $response->json('order_tracking_id')) {
                return [
                    'success' => true,
                    'order_tracking_id' => $response->json('order_tracking_id'),
                    'redirect_url' => $response->json('redirect_url'),
                    'merchant_reference' => $response->json('merchant_reference'),
                ];
            }

            Log::error('PesaPal order request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'PesaPal order request failed: ' . substr($response->body(), 0, 300)];
        } catch (\Exception $e) {
            Log::error('PesaPal order request error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'PesaPal error: ' . $e->getMessage()];
        }
    }

    /**
     * Check the current status of a transaction (authoritative, used by callback/IPN).
     *
     * @return array{success: bool, message?: string, status?: string, confirmation_code?: string|null, payment_method?: string|null, amount?: float|null, currency?: string|null, merchant_reference?: string|null, paid_at?: string|null, raw?: array}
     */
    public function checkStatus(string $orderTrackingId): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Failed to get PesaPal token.'];
        }

        try {
            $response = Http::withToken($token)->timeout(30)->get($this->baseUrl . '/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId,
            ]);

            if ($response->successful()) {
                $data = (array) $response->json();

                return [
                    'success' => true,
                    'status' => $data['payment_status_description'] ?? null,
                    'status_code' => $data['status_code'] ?? null,
                    'confirmation_code' => $data['confirmation_code'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'merchant_reference' => $data['merchant_reference'] ?? null,
                    'paid_at' => $data['created_date'] ?? null,
                    'raw' => $data,
                ];
            }

            Log::error('PesaPal status check failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'PesaPal status check failed.'];
        } catch (\Exception $e) {
            Log::error('PesaPal status check error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'PesaPal status check error: ' . $e->getMessage()];
        }
    }
}