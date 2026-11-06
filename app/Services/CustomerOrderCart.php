<?php

namespace App\Services;

/**
 * The website's shopping cart for a customer order.
 *
 * An order always belongs to ONE branch — that is how checkout works (one
 * branch_id + a list of items) and how stock is packed. The cart therefore
 * holds one branch at a time: opening a product at another branch starts a
 * fresh cart instead of mixing two branches into an order that cannot exist.
 *
 * The cart lives in the session, so a customer can add a perfume, go back to
 * the shop for another, and still check out in one go. This mirrors the mobile
 * app's cart (world_choice_perfume_mobile/lib/cart.tsx).
 */
class CustomerOrderCart
{
    private const ITEMS_KEY = 'customer_order_cart';

    private const CONTACT_KEY = 'customer_order_contact';

    /**
     * The lines in the cart, oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        $items = session(self::ITEMS_KEY, []);

        return is_array($items) ? array_values($items) : [];
    }

    /** The branch the cart is packed from, or null when it is empty. */
    public function branchId(): ?int
    {
        $branchId = (int) ($this->items()[0]['branch_id'] ?? 0);

        return $branchId > 0 ? $branchId : null;
    }

    public function branchName(): ?string
    {
        $name = (string) ($this->items()[0]['branch_name'] ?? '');

        return $name !== '' ? $name : null;
    }

    /** Total number of bottles, not number of lines. */
    public function count(): int
    {
        $count = 0;
        foreach ($this->items() as $item) {
            $count += max(1, (int) ($item['quantity'] ?? 1));
        }

        return $count;
    }

    public function subtotal(): float
    {
        $total = 0.0;
        foreach ($this->items() as $item) {
            $total += (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
        }

        return $total;
    }

    /**
     * Add a line, or replace the line that already holds this product + size.
     *
     * The order form's product card edits its own cart line, so the quantity
     * it posts is the quantity that line should have — not an addition.
     *
     * @param  array<string, mixed>  $item
     */
    public function put(array $item): void
    {
        $item['key'] = $item['key'] ?? self::keyFor(
            (int) ($item['product_id'] ?? 0),
            (int) ($item['volume'] ?? 0)
        );

        $items = $this->items();

        foreach ($items as $index => $existing) {
            if ((string) ($existing['key'] ?? '') === $item['key']) {
                $items[$index] = $item;
                session()->put(self::ITEMS_KEY, $items);

                return;
            }
        }

        $items[] = $item;
        session()->put(self::ITEMS_KEY, $items);
    }

    public function forget(string $key): void
    {
        $items = array_values(array_filter(
            $this->items(),
            fn ($item) => (string) ($item['key'] ?? '') !== $key
        ));

        if ($items === []) {
            $this->clear();

            return;
        }

        session()->put(self::ITEMS_KEY, $items);
    }

    public function clear(): void
    {
        session()->forget([self::ITEMS_KEY, self::CONTACT_KEY]);
    }

    /** A line is one product in one size — 50ml and 30ml are separate lines. */
    public static function keyFor(int $productId, int $volume = 0): string
    {
        return $productId . '|' . max(0, $volume);
    }

    /**
     * The customer details entered on the order form, kept so returning to the
     * shop and picking another perfume does not ask for them a second time.
     *
     * @return array{name: string, phone: string, email: string, notes: string}
     */
    public function contact(): array
    {
        $contact = session(self::CONTACT_KEY, []);

        return [
            'name' => (string) ($contact['name'] ?? ''),
            'phone' => (string) ($contact['phone'] ?? ''),
            'email' => (string) ($contact['email'] ?? ''),
            'notes' => (string) ($contact['notes'] ?? ''),
        ];
    }

    /**
     * @param  array{name?: string, phone?: string, email?: string, notes?: string}  $contact
     */
    public function rememberContact(array $contact): void
    {
        // An empty form must not wipe details that were already saved.
        if (trim((string) ($contact['name'] ?? '')) === '' && trim((string) ($contact['phone'] ?? '')) === '') {
            return;
        }

        session()->put(self::CONTACT_KEY, array_merge($this->contact(), array_filter([
            'name' => trim((string) ($contact['name'] ?? '')),
            'phone' => trim((string) ($contact['phone'] ?? '')),
            'email' => trim((string) ($contact['email'] ?? '')),
            'notes' => trim((string) ($contact['notes'] ?? '')),
        ], fn ($value) => $value !== '')));
    }
}
