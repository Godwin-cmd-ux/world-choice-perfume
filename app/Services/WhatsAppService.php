<?php

namespace App\Services;

class WhatsAppService
{
    /**
     * Country code assumed when a number is stored without one.
     *
     * The shop is in Tanzania and the client form asks for a WhatsApp number
     * with no guidance on format, so a member types the local number they know
     * by, which starts with a zero. WhatsApp will not resolve that, so the code
     * is filled in here.
     */
    public const COUNTRY_CODE = '255';

    /**
     * Lengths a number may plausibly have once it is in international form.
     *
     * E.164 allows fifteen digits and no more. The floor rejects the short
     * remnants that reach a free text column by accident, because a link that
     * opens a chat with the wrong person is worse than no link at all.
     */
    private const MIN_DIGITS = 9;

    private const MAX_DIGITS = 15;

    /**
     * A wa.me link that opens a chat with this number.
     *
     * `wa.me` is addressed by country code and digits only. Spaces, dashes,
     * brackets and a leading plus all stop it resolving, and a local number
     * kept as typed is not a number WhatsApp can route, so the stored value is
     * reduced to what it needs here rather than at the point of entry, where
     * it also has to stay searchable and readable on the record.
     *
     * Returns null when what is stored cannot be dialled, so a caller can show
     * the number as it is instead of hanging a broken link off it.
     */
    public function chatLink(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        // A leading zero marks the local form: 0710... is 710... inside 255.
        if (str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE.ltrim($digits, '0');
        } elseif (! str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = self::COUNTRY_CODE.$digits;
        }

        $length = strlen($digits);

        if ($length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

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
