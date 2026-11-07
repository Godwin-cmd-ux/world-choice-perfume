<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Customer lookup and creation, with the phone number as the identity.
 *
 * A customer is the same person however their number was typed, so every
 * creation path asks this service first. When the normalized number is already
 * on file the stored record is returned and no second customer is written —
 * even when the submitted name is different. Names are never merged or
 * overwritten; a different name must not bypass phone validation.
 *
 * Simultaneous requests are kept apart by the unique index on
 * customers.phone_normalized (see the migration): the losing insert fails and
 * is resolved to the row the winner created.
 */
class CustomerService
{
    private SupabaseService $supabase;

    private PhoneNumberService $phones;

    private ?bool $hasNormalizedColumn = null;

    public function __construct(?SupabaseService $supabase = null, ?PhoneNumberService $phones = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
        $this->phones = $phones ?? new PhoneNumberService();
    }

    /** The normalizer, for callers that need to display the canonical form. */
    public function phones(): PhoneNumberService
    {
        return $this->phones;
    }

    /**
     * Existing customers matching a name or phone search term.
     *
     * PostgREST cannot OR across columns, so name and phone are queried
     * separately and the hits are merged by id. Phone searches compare on the
     * significant digits, so a formatted query still finds a canonical row.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        $limit = max(1, $limit);

        if ($query === '') {
            return [];
        }

        $hits = $this->supabase->query('customers', [
            'select' => '*',
            'name' => 'ilike.*'.urlencode($query).'*',
            'order' => 'name.asc',
            'limit' => $limit,
        ]);

        $digits = preg_replace('/\D+/', '', $query);

        if (is_string($digits) && strlen($digits) >= 3) {
            $normalized = $this->phones->normalize($query);

            if ($normalized !== null && $this->hasNormalizedColumn()) {
                $hits = array_merge($hits, $this->supabase->query('customers', [
                    'select' => '*',
                    'phone_normalized' => 'eq.'.$normalized,
                    'limit' => $limit,
                ]));
            }

            // The last seven digits identify the subscriber whether the query
            // arrived as 0682 6011 54 or +255682601154.
            $tail = strlen($digits) >= 7 ? substr($digits, -7) : $digits;

            $hits = array_merge($hits, $this->supabase->query('customers', [
                'select' => '*',
                'phone' => 'ilike.*'.$tail.'*',
                'order' => 'name.asc',
                'limit' => $limit,
            ]));
        }

        $merged = [];
        foreach ($hits as $row) {
            if (isset($row['id'])) {
                $merged[(int) $row['id']] = $row;
            }
        }

        usort($merged, fn ($a, $b) => strcasecmp(
            (string) ($a['name'] ?? ''),
            (string) ($b['name'] ?? '')
        ));

        return array_slice(array_values($merged), 0, $limit);
    }

    /**
     * The customer already holding this phone number, in any stored format.
     *
     * @return array<string, mixed>|null
     */
    public function findExistingByPhone(?string $phone): ?array
    {
        $normalized = $this->phones->normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $rows = [];

        // Exact match on the number as submitted — the cheapest hit, and the
        // one older records stored verbatim will answer.
        $exact = $this->supabase->findOne('customers', ['phone' => trim((string) $phone)]);
        if ($exact !== null && $this->phones->normalize((string) ($exact['phone'] ?? '')) === $normalized) {
            return $exact;
        }

        // Fast path: the normalized column written by every new create.
        if ($this->hasNormalizedColumn()) {
            $rows = array_merge($rows, $this->supabase->query('customers', [
                'select' => '*',
                'phone_normalized' => 'eq.'.$normalized,
                'limit' => 5,
            ]));
        }

        // Rows written before the column existed, stored as typed.
        $variants = $this->phones->variants($normalized);
        if ($variants !== []) {
            $rows = array_merge($rows, $this->supabase->query('customers', [
                'select' => '*',
                'phone' => 'in.('.implode(',', $variants).')',
                'limit' => 10,
            ]));
        }

        // The same number typed with spaces, hyphens or brackets.
        $tail = substr($normalized, -7);
        $rows = array_merge($rows, $this->supabase->query('customers', [
            'select' => '*',
            'phone' => 'ilike.*'.$tail.'*',
            'limit' => 25,
        ]));

        $seen = [];
        foreach ($rows as $row) {
            if (! isset($row['id']) || isset($seen[(int) $row['id']])) {
                continue;
            }
            $seen[(int) $row['id']] = true;

            if ($this->phones->normalize((string) ($row['phone'] ?? '')) === $normalized) {
                return $row;
            }
        }

        // Without the normalized column, older rows can hold any formatting.
        // A bounded scan compares them by their normalized value so a spaced
        // legacy number is still recognized as the same subscriber.
        if (! $this->hasNormalizedColumn()) {
            foreach ($this->supabase->query('customers', [
                'select' => '*',
                'phone' => 'not.is.null',
                'order' => 'created_at.desc',
                'limit' => 200,
            ]) as $row) {
                if (! isset($row['id'])) {
                    continue;
                }
                if ($this->phones->normalize((string) ($row['phone'] ?? '')) === $normalized) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Return the customer holding this phone number, or create one.
     *
     * @return array{customer: array<string, mixed>, duplicate: bool, created: bool}
     *
     * @throws ValidationException when the phone number is not usable
     * @throws \RuntimeException when the record could not be written
     */
    public function createOrRetrieve(?string $name, ?string $phone, array $extra = []): array
    {
        $name = trim((string) $name);
        $rawPhone = trim((string) $phone);
        $normalized = null;

        if ($rawPhone !== '') {
            $normalized = $this->phones->normalize($rawPhone);

            if ($normalized === null) {
                throw ValidationException::withMessages([
                    'phone' => PhoneNumberService::INVALID_MESSAGE,
                ]);
            }

            $existing = $this->findExistingByPhone($rawPhone);

            if ($existing !== null) {
                $this->rememberNormalized($existing, $normalized);

                return ['customer' => $existing, 'duplicate' => true, 'created' => false];
            }
        }

        if ($name === '' && $normalized === null) {
            throw ValidationException::withMessages([
                'name' => 'Enter the customer name or phone number.',
            ]);
        }

        $whatsapp = $extra['whatsapp'] ?? null;
        unset($extra['whatsapp']);

        $data = array_merge([
            'name' => $name !== '' ? $name : null,
            'phone' => $normalized,
            'whatsapp' => $normalized,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], $extra);

        if ($whatsapp !== null && trim((string) $whatsapp) !== '') {
            $data['whatsapp'] = $this->phones->normalize((string) $whatsapp) ?? trim((string) $whatsapp);
        }

        if ($normalized !== null && $this->hasNormalizedColumn()) {
            $data['phone_normalized'] = $normalized;
        }

        $customer = $this->supabase->insert('customers', $data);

        if (! $customer) {
            // A simultaneous request may have inserted the same normalized
            // number between our lookup and this insert. The unique index
            // rejects the loser; resolve it to the winner's row.
            SupabaseService::clearCache();

            $existing = $normalized !== null ? $this->findExistingByPhone($rawPhone) : null;

            if ($existing !== null) {
                $this->rememberNormalized($existing, $normalized);

                return ['customer' => $existing, 'duplicate' => true, 'created' => false];
            }

            throw new \RuntimeException('Could not save the customer. Please try again.');
        }

        return ['customer' => $customer, 'duplicate' => false, 'created' => true];
    }

    /**
     * Whether the installed schema carries the normalized column. A driver
     * that cannot answer (or a test double) is treated as not having it, so
     * the phone-variant checks alone still keep duplicates out.
     */
    private function hasNormalizedColumn(): bool
    {
        if ($this->hasNormalizedColumn === null) {
            try {
                $this->hasNormalizedColumn = $this->supabase->tableHasColumn('customers', 'phone_normalized');
            } catch (\Throwable $e) {
                $this->hasNormalizedColumn = false;
            }
        }

        return $this->hasNormalizedColumn;
    }

    /**
     * Fill the derived column on an older row the first time it is matched, so
     * the fast path finds it next time. Nothing the customer entered changes.
     *
     * @param  array<string, mixed>  $customer
     */
    private function rememberNormalized(array $customer, string $normalized): void
    {
        if (! $this->hasNormalizedColumn() || empty($customer['id'])) {
            return;
        }

        if (($customer['phone_normalized'] ?? null) === $normalized) {
            return;
        }

        $this->supabase->update('customers', [
            'phone_normalized' => $normalized,
        ], ['id' => $customer['id']]);
    }
}
