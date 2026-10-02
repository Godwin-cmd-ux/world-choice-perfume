<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerCare\InquiryController;
use App\Models\User;
use App\Services\SupabaseService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Deleting a customer inquiry.
 *
 * The inquiry row carries its own message and reply, so removing it takes
 * nothing else with it. The controller builds its own Supabase client, so the
 * service is swapped in through the private property and the method is driven
 * directly; this covers the two things the interface depends on — that only
 * the signed-in member's own branch can be deleted, and that a failed delete
 * is reported rather than silently redirecting as a success.
 */
class InquiryDeleteTest extends TestCase
{
    private function supabaseWith(array $rows, bool $deleteSucceeds = true): SupabaseService
    {
        return new class($rows, $deleteSucceeds) extends SupabaseService
        {
            public array $deleted = [];

            public function __construct(private array $rows, private bool $deleteSucceeds)
            {
            }

            public function find(string $table, int|string $id, string $select = '*'): ?array
            {
                foreach ($this->rows as $row) {
                    if ((int) ($row['id'] ?? 0) === (int) $id) {
                        return $row;
                    }
                }

                return null;
            }

            public function delete(string $table, array $conditions): bool
            {
                $this->deleted[] = ['table' => $table, 'conditions' => $conditions];

                return $this->deleteSucceeds;
            }
        };
    }

    private function controllerWith(SupabaseService $supabase): InquiryController
    {
        $controller = (new \ReflectionClass(InquiryController::class))->newInstanceWithoutConstructor();

        $property = new \ReflectionProperty(InquiryController::class, 'supabase');
        $property->setAccessible(true);
        $property->setValue($controller, $supabase);

        return $controller;
    }

    private function careMember(int $branchId): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'name' => 'Gideon Msuya',
            'email' => 'gideonmsuya140@gmail.com',
            'role' => 'customer_care',
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_an_inquiry_of_the_members_branch_is_deleted(): void
    {
        $this->be($this->careMember(10));
        $supabase = $this->supabaseWith([
            ['id' => 7, 'branch_id' => 10, 'subject' => 'Reef 33 price'],
        ]);

        $response = $this->controllerWith($supabase)->destroy(7);

        $this->assertSame(route('customer-care.inquiries.index'), $response->getTargetUrl());
        $this->assertSame('Inquiry deleted.', session('success'));
        $this->assertSame([['table' => 'inquiries', 'conditions' => ['id' => 7]]], $supabase->deleted);
    }

    public function test_an_inquiry_of_another_branch_cannot_be_deleted(): void
    {
        $this->be($this->careMember(10));
        $supabase = $this->supabaseWith([
            ['id' => 8, 'branch_id' => 99, 'subject' => 'Someone else'],
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->controllerWith($supabase)->destroy(8);
    }

    public function test_a_missing_inquiry_is_a_404(): void
    {
        $this->be($this->careMember(10));

        $this->expectException(NotFoundHttpException::class);

        $this->controllerWith($this->supabaseWith([]))->destroy(999);
    }

    public function test_a_failed_delete_reports_an_error_and_deletes_nothing(): void
    {
        $this->be($this->careMember(10));
        $supabase = $this->supabaseWith([
            ['id' => 9, 'branch_id' => 10, 'subject' => 'By mistake'],
        ], deleteSucceeds: false);

        $response = $this->controllerWith($supabase)->destroy(9);

        $this->assertSame('The inquiry could not be deleted. Please try again.', session('error'));
        $this->assertNotSame(
            route('customer-care.inquiries.index'),
            $response->getTargetUrl(),
            'a failed delete must not redirect as though it succeeded'
        );
    }

    public function test_a_guest_cannot_reach_the_delete_route(): void
    {
        $this->delete('/customer-care/inquiries/7')->assertRedirect();
    }
}
