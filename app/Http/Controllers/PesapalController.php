<?php

namespace App\Http\Controllers;

use App\Services\PesapalService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PesapalController extends Controller
{
    /**
     * Redirect callback from PesaPal after the customer tries to pay.
     */
    public function callback(Request $request)
    {
        $pesapal = new PesapalService();
        $supabase = new SupabaseService();

        $orderTrackingId = $request->query('OrderTrackingId', $request->query('order_tracking_id'));
        $orderMerchantReference = $request->query('OrderMerchantReference', $request->query('order_merchant_reference'));

        if (!$orderTrackingId) {
            return redirect()->route('customer.orders.track')->with('error', 'Invalid payment callback.');
        }

        $result = $pesapal->checkStatus($orderTrackingId);
        $status = $result['success'] ? strtoupper((string) $result['status']) : '';

        if ($status === 'COMPLETED') {
            $this->markTransactionPaid($supabase, $orderTrackingId, $orderMerchantReference, $result);
            Log::info('PesaPal callback: payment completed', ['tracking_id' => $orderTrackingId]);

            return redirect()->route('customer.orders.track')
                ->with('success', 'Payment confirmed! Your order is being processed.');
        }

        if (in_array($status, ['FAILED', 'CANCELLED', 'DECLINED', 'REVERSED', 'INVALID'])) {
            $this->markTransactionFailed($supabase, $orderTrackingId);
        }

        return redirect()->route('customer.orders.track')
            ->with('error', 'Payment not completed. Status: ' . ($status ?: 'Unknown') . '. Please contact support.');
    }

    /**
     * PesaPal IPN (Instant Payment Notification) endpoint.
     */
    public function ipn(Request $request)
    {
        $payload = $request->input();

        $orderTrackingId = $payload['pesapal_transaction_tracking_id']
            ?? $payload['OrderTrackingId']
            ?? null;

        $orderMerchantReference = $payload['pesapal_merchant_reference']
            ?? $payload['OrderMerchantReference']
            ?? null;

        if (!$orderTrackingId) {
            return response()->json(['error' => 'Missing tracking ID'], 400);
        }

        $pesapal = new PesapalService();
        $result = $pesapal->checkStatus($orderTrackingId);

        $status = $result['success'] ? strtoupper((string) $result['status']) : '';

        if ($status === 'COMPLETED') {
            $this->markTransactionPaid(new SupabaseService(), $orderTrackingId, $orderMerchantReference, $result);
            Log::info('PesaPal IPN: payment completed', ['tracking_id' => $orderTrackingId]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Mark the matching order (or SALE-* sale) as paid.
     */
    private function markTransactionPaid(SupabaseService $supabase, string $trackingId, ?string $merchantReference, array $result): void
    {
        $order = $supabase->findOne('orders', ['pesapal_tracking_id' => $trackingId]);

        if ($order) {
            $supabase->update('orders', [
                'payment_status' => 'paid',
                'payment_method' => 'pesapal',
                'payment_confirmation_code' => $result['confirmation_code'] ?? null,
                'paid_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => (int) $order['id']]);

            return;
        }

        if ($merchantReference && str_starts_with((string) $merchantReference, 'SALE-')) {
            $supabase->update('sales', [
                'payment_status' => 'paid',
                'payment_method' => 'pesapal',
                'updated_at' => now()->toIso8601String(),
            ], ['sale_number' => $merchantReference]);
        }
    }

    /**
     * Mark the matching order as failed on a failed/cancelled payment.
     */
    private function markTransactionFailed(SupabaseService $supabase, string $trackingId): void
    {
        $order = $supabase->findOne('orders', ['pesapal_tracking_id' => $trackingId]);

        if ($order) {
            $supabase->update('orders', [
                'payment_status' => 'failed',
                'updated_at' => now()->toIso8601String(),
            ], ['id' => (int) $order['id']]);
        }
    }
}