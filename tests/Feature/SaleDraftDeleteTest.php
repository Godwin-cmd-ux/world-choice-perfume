<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Deleting a parked draft sale from the Drafts list.
 *
 * A draft is owned by its branch: the delete only reaches the database when
 * the row exists and belongs to the same branch the list showed. A refused
 * delete is reported instead of being claimed as success.
 */
class SaleDraftDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
    }

    public function test_a_cashier_deletes_their_own_draft(): void
    {
        $deleted = [];

        $this->fakeDraft(11, $deleted);

        $response = $this->delete('/cashier/sales/draft/9');

        $response->assertRedirect();
        $this->assertStringContainsString('Draft sale deleted.', (string) session('success'));
        $this->assertCount(1, $deleted, 'the draft delete reached the database');
    }

    public function test_a_draft_of_another_branch_is_not_deleted(): void
    {
        $deleted = [];

        // The signed-in cashier is at branch 11; the draft belongs to 12.
        $this->fakeDraft(12, $deleted);

        $response = $this->delete('/cashier/sales/draft/9');

        $response->assertRedirect();
        $this->assertStringContainsString('could not be found', (string) session('error'));
        $this->assertNull(session('success'));
        $this->assertCount(0, $deleted, 'nothing was sent to the database');
    }

    public function test_a_database_refusal_is_reported_not_hidden(): void
    {
        $deleted = [];

        $this->fakeDraft(11, $deleted, deleteStatus: 500);

        $response = $this->delete('/cashier/sales/draft/9');

        $response->assertRedirect();
        $this->assertStringContainsString('Could not delete the draft', (string) session('error'));
        $this->assertNull(session('success'));
        $this->assertNotEmpty($deleted, 'the delete was attempted');
    }

    public function test_the_confirm_dialog_stacks_above_the_drafts_modal(): void
    {
        // The delete button opens the shared confirm dialog. The drafts modal is
        // also a full-screen layer, so the confirmation must sit above it or the
        // Delete click appears to do nothing.
        $toast = (string) file_get_contents(resource_path('views/partials/toast.blade.php'));
        $panel = (string) file_get_contents(resource_path('views/partials/sale-drafts-panel.blade.php'));

        $this->assertSame(1, preg_match('/id="toastConfirm"[^>]*z-\[(\d+)\]/', $toast, $confirm));
        $this->assertSame(1, preg_match('/id="saleDraftsModal"[^>]*z-\[(\d+)\]/', $panel, $drafts));
        $this->assertGreaterThan((int) $drafts[1], (int) $confirm[1]);
    }

    /**
     * Fake a customer care / cashier style draft lookup and delete.
     *
     * @param  array<int, string>  $deleted  Receives every delete URL sent.
     */
    private function fakeDraft(int $draftBranchId, array &$deleted, int $deleteStatus = 204): void
    {
        Http::fake(function (Request $request) use ($draftBranchId, &$deleted, $deleteStatus) {
            $url = urldecode($request->url());

            if ($request->method() === 'DELETE' && str_contains($url, '/rest/v1/sale_drafts')) {
                $deleted[] = $url;

                return Http::response('', $deleteStatus);
            }

            if ($request->method() === 'HEAD') {
                return Http::response('', 200, ['Content-Range' => '0-0/1']);
            }

            if (str_contains($url, '/rest/v1/sale_drafts')) {
                if (preg_match('/id=eq\.(\d+)/', $url, $m)) {
                    return Http::response([[
                        'id' => (int) $m[1],
                        'branch_id' => $draftBranchId,
                        'created_by' => 31,
                        'customer_label' => 'Amina Yusuf',
                        'total' => 150000,
                        'item_count' => 2,
                        'payload' => ['items' => []],
                        'created_at' => '2026-11-01T09:00:00+03:00',
                        'updated_at' => '2026-11-01T09:00:00+03:00',
                    ]], 200);
                }

                return Http::response([], 200);
            }

            return Http::response([], 200);
        });

        $user = new User;
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Neema Joseph',
            'email' => 'neema@example.co.tz',
            'role' => 'cashier',
            'branch_id' => 11,
            'status' => 'active',
        ]);
        $this->be($user);
    }
}
