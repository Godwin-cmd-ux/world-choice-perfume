<?php

namespace App\Services;

class WhatsAppService
{
    /**
     * Send receipt via WhatsApp Business API (stub for Phase 1)
     * This will be implemented when a WhatsApp Business API provider is selected.
     */
    public function sendReceipt(object $sale): bool
    {
        // Phase 1 stub - logs the intent
        \Log::info("WhatsApp receipt would be sent for Sale #{$sale->sale_number}", [
            'customer_phone' => $sale->customer?->whatsapp ?? $sale->customer?->phone ?? null,
            'total' => $sale->total,
        ]);

        // TODO: Implement when WhatsApp Business API provider is selected
        // 1. Generate PDF receipt
        // 2. Upload to WhatsApp Business API
        // 3. Send to customer's WhatsApp number

        return true;
    }
}
