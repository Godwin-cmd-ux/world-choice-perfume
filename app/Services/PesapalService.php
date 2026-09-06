<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl = 'https://pay.pesapal.com/v3/api';
    private string $authUrl = 'https://pay.pesapal.com/v3/api/Auth/RequestToken';

    public function __construct()
    {
        $this->consumerKey = config('services.pesapal.consumer_key', '');
        $this->consumerSecret = config('services.pesapal.consumer_secret', '');
    }

    /**
     * Get OAuth token from Pesapal
     */
    public function getToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->post($this->authUrl);

            if ($response->successful()) {
                return $response->json('token');
            }

            Log::error('Pesapal token request failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Pesapal token request error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initiate an STK push payment (M-Pesa via Pesapal)
     */
    public function initiatePayment(array $params): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Failed to get Pesapal token.'];
        }

        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/Transactions/Initiate', [
                    'id' => $params['order_id'] ?? uniqid('ORD-'),
                    'currency' => $params['currency'] ?? 'TZS',
                    'amount' => $params['amount'] ?? 0,
                    'description' => $params['description'] ?? 'Payment for order',
                    'callback_url' => $params['callback_url'] ?? route('pesapal.callback'),
                    'billing_address' => [
                        'email_address' => $params['email'] ?? '',
                        'phone_number' => $params['phone'] ?? '',
                        'first_name' => $params['first_name'] ?? '',
                        'last_name' => $params['last_name'] ?? '',
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'order_tracking_id' => $response->json('order_tracking_id'),
                    'redirect_url' => $response->json('redirect_url'),
                    'merchant_reference' => $response->json('merchant_reference'),
                ];
            }

            Log::error('Pesapal payment initiation failed', ['response' => $response->body()]);
            return ['success' => false, 'message' => 'Payment initiation failed.'];
        } catch (\Exception $e) {
            Log::error('Pesapal payment initiation error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment error: ' . $e->getMessage()];
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(string $orderTrackingId): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Failed to get Pesapal token.'];
        }

        try {
            $response = Http::withToken($token)
                ->get($this->baseUrl . '/Transactions/GetTransactionStatus', [
                    'orderTrackingId' => $orderTrackingId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json('status'),
                    'payment_method' => $response->json('payment_method'),
                    'amount' => $response->json('amount'),
                    'currency' => $response->json('currency'),
                ];
            }

            return ['success' => false, 'message' => 'Status check failed.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Status check error: ' . $e->getMessage()];
        }
    }
}
