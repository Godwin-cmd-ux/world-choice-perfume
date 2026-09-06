<?php

namespace App\Http\Controllers;

use App\Services\PesapalService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class PesapalController extends Controller
{
    public function callback(Request $request)
    {
        $pesapal = new PesapalService();
        $supabase = new SupabaseService();

        $orderTrackingId = $request->query('OrderTrackingId');
        $orderMerchantReference = $request->query('OrderMerchantReference');

        if (!$orderTrackingId) {
            return redirect()->route('home')->with('error', 'Invalid payment callback.');
        }

        $result = $pesapal->checkStatus($orderTrackingId);

        if ($result['success'] && ($result['status'] ?? '') === 'COMPLETED') {
            // Payment successful - this is called from the customer order flow
            // The actual order processing is handled by the OrderController
            return redirect()->route('customer.orders.track')
                ->with('success', 'Payment confirmed! Your order is being processed.');
        }

        return redirect()->route('customer.orders.track')
            ->with('error', 'Payment not confirmed. Please contact support.');
    }

    /**
     * API endpoint for Pesapal IPN (Instant Payment Notification)
     */
    public function ipn(Request $request)
    {
        $pesapal = new PesapalService();
        $supabase = new SupabaseService();

        $orderTrackingId = $request->input('OrderTrackingId');
        $orderMerchantReference = $request->input('OrderMerchantReference');
        $transactionStatus = $request->input('TransactionStatus');

        if (!$orderTrackingId) {
            return response()->json(['error' => 'Missing tracking ID'], 400);
        }

        $result = $pesapal->checkStatus($orderTrackingId);

        if ($result['success'] && ($result['status'] ?? '') === 'COMPLETED') {
            // Update sale payment status if this is a sale order
            if (str_starts_with((string) $orderMerchantReference, 'SALE-')) {
                $supabase->update('sales', [
                    'payment_status' => 'paid',
                    'payment_method' => 'mobile_payment',
                    'updated_at' => now()->toIso8601String(),
                ], ['sale_number' => $orderMerchantReference]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
