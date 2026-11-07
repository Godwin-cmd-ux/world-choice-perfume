<?php

namespace App\Services;

/**
 * One canonical form for a Tanzanian phone number.
 *
 * The shop hears the same number in many shapes — 0682601154, 0682 6011 54,
 * +255682601154, 255682601154, +255 682 601 154 — and every one of them is the
 * same subscriber. Formatting is stripped first, then the local 0XXXXXXXXX
 * form and the international +255XXXXXXXXX form are folded into a single
 * canonical value so they can be compared with a plain string equality.
 */
class PhoneNumberService
{
    /** Message shown when a number cannot be understood as a Tanzanian mobile. */
    public const INVALID_MESSAGE = 'Enter a valid Tanzanian phone number, e.g. 0682 601154 or +255 682 601 154.';

    /**
     * Canonical +255XXXXXXXXX form, or null when the number is not a valid
     * Tanzanian mobile number.
     */
    public function normalize(?string $phone): ?string
    {
        $raw = trim((string) $phone);

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        // 00255... is the international dialling prefix for +255.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '255')) {
            $national = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            // Already the national significant number, without the leading 0.
            $national = $digits;
        } else {
            return null;
        }

        // Tanzanian mobiles are 6XXXXXXXX or 7XXXXXXXX (nine digits).
        if (strlen($national) !== 9 || ! preg_match('/^[67]\d{8}$/', $national)) {
            return null;
        }

        return '+255'.$national;
    }

    /** The local form customers read back: 0XXXXXXXXX. */
    public function local(?string $phone): ?string
    {
        $normalized = $this->normalize($phone);

        return $normalized === null ? null : '0'.substr($normalized, 4);
    }

    /**
     * Every literal shape the same number may already have been stored in
     * before this normalization existed.
     *
     * @return array<int, string>
     */
    public function variants(?string $phone): array
    {
        $normalized = $this->normalize($phone);

        if ($normalized === null) {
            return [];
        }

        $national = substr($normalized, 4);

        return array_values(array_unique([
            $normalized,          // +255682601154
            '255'.$national,      // 255682601154
            '0'.$national,        // 0682601154
            $national,            // 682601154
        ]));
    }

    public function isValid(?string $phone): bool
    {
        return $this->normalize($phone) !== null;
    }
}
