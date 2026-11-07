<?php

namespace Tests\Unit;

use App\Services\CustomerService;
use App\Services\PhoneNumberService;
use App\Services\SupabaseService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The phone number, not the typed name, decides who a customer is.
 *
 * The shapes the same Tanzanian number arrives in are pinned first, then the
 * create path is exercised against an in-memory customer table to prove that a
 * second spelling retrieves the first record instead of writing a duplicate —
 * even when the submitted name is different.
 */
class CustomerDuplicatePhoneTest extends TestCase
{
    public function test_tanzanian_phone_formats_normalize_to_one_value(): void
    {
        $phones = new PhoneNumberService();

        $same = [
            '0682601154',
            '0682 6011 54',
            '+255682601154',
            '255682601154',
            '+255 682 601 154',
        ];

        foreach ($same as $number) {
            $this->assertSame('+255682601154', $phones->normalize($number), $number);
        }

        // Genuinely different subscribers stay different.
        $this->assertSame('+255712345678', $phones->normalize('0712345678'));
        $this->assertNotSame($phones->normalize('0682601154'), $phones->normalize('0682601155'));

        // Unsupported formats are refused rather than guessed at.
        $this->assertNull($phones->normalize('12345'));
        $this->assertNull($phones->normalize('+2550682601154'));
        $this->assertNull($phones->normalize(''));
    }

    public function test_a_known_phone_returns_the_existing_customer_whatever_the_name(): void
    {
        $supabase = new InMemoryCustomerSupabase();
        $customers = new CustomerService($supabase);

        $first = $customers->createOrRetrieve('Asha Juma', '0682601154');
        $this->assertTrue($first['created']);
        $this->assertFalse($first['duplicate']);
        $this->assertCount(1, $supabase->customers);

        foreach ([['Neema Ally', '+255682601154'], ['Zawadi Said', '0682 6011 54'], ['', '255682601154']] as [$name, $phone]) {
            $result = $customers->createOrRetrieve($name, $phone);

            $this->assertFalse($result['created']);
            $this->assertTrue($result['duplicate']);
            $this->assertSame($first['customer']['id'], $result['customer']['id']);
            $this->assertSame('Asha Juma', $result['customer']['name'], 'the existing record is never renamed');
        }

        $this->assertCount(1, $supabase->customers, 'no duplicate customer rows were written');
    }

    public function test_different_numbers_create_different_customers(): void
    {
        $supabase = new InMemoryCustomerSupabase();
        $customers = new CustomerService($supabase);

        $customers->createOrRetrieve('Asha Juma', '0682601154');
        $second = $customers->createOrRetrieve('Neema Ally', '0712345678');

        $this->assertTrue($second['created']);
        $this->assertCount(2, $supabase->customers);
    }

    public function test_an_unsupported_phone_number_is_refused(): void
    {
        $customers = new CustomerService(new InMemoryCustomerSupabase());

        $this->expectException(ValidationException::class);
        $customers->createOrRetrieve('Asha Juma', '12345');
    }

    public function test_a_failed_insert_is_resolved_to_the_row_a_concurrent_request_created(): void
    {
        // The fake reports the insert as failed (the unique index rejecting a
        // simultaneous create) but already holds the winner's row.
        $supabase = new InMemoryCustomerSupabase();
        $supabase->customers[] = [
            'id' => 7,
            'name' => 'Asha Juma',
            'phone' => '+255682601154',
            'whatsapp' => '+255682601154',
        ];
        $supabase->failInserts = true;

        $result = (new CustomerService($supabase))->createOrRetrieve('Different Name', '0682601154');

        $this->assertTrue($result['duplicate']);
        $this->assertSame(7, $result['customer']['id']);
        $this->assertCount(1, $supabase->customers);
    }
}

/**
 * Just enough of the data layer to prove the customer identity rules without a
 * database or HTTP. Only the customers table is used by CustomerService.
 */
class InMemoryCustomerSupabase extends SupabaseService
{
    /** @var array<int, array<string, mixed>> */
    public array $customers = [];

    public bool $failInserts = false;

    public function tableHasColumn(string $table, string $column): bool
    {
        return false;
    }

    public function findOne(string $table, array $conditions, string $select = '*'): ?array
    {
        foreach ($this->customers as $row) {
            $match = true;
            foreach ($conditions as $column => $value) {
                if ((string) ($row[$column] ?? '') !== (string) $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $row;
            }
        }

        return null;
    }

    public function query(string $table, array $params = []): array
    {
        if ($table !== 'customers') {
            return [];
        }

        $filter = $params['phone'] ?? null;

        if (! is_string($filter)) {
            return array_values($this->customers);
        }

        if ($filter === 'not.is.null') {
            return array_values(array_filter($this->customers, fn ($row) => ($row['phone'] ?? null) !== null));
        }

        if (str_starts_with($filter, 'in.(')) {
            $wanted = array_map('trim', explode(',', trim(substr($filter, 4), '()')));
            return array_values(array_filter(
                $this->customers,
                fn ($row) => in_array((string) ($row['phone'] ?? ''), $wanted, true)
            ));
        }

        if (str_starts_with($filter, 'ilike.*')) {
            $needle = trim(substr($filter, 7), '*');
            return array_values(array_filter(
                $this->customers,
                fn ($row) => str_contains((string) ($row['phone'] ?? ''), $needle)
            ));
        }

        return array_values($this->customers);
    }

    public function insert(string $table, array|object $data): ?array
    {
        if ($table !== 'customers' || $this->failInserts) {
            return null;
        }

        $row = (array) $data;
        $row['id'] = count($this->customers) + 1;
        $this->customers[] = $row;

        return $row;
    }
}
