<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerCare\Concerns\ResolvesClientRecords;
use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ResolvesClientRecords;

    private SupabaseService $supabase;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    /**
     * Customer care owns five tabs: the review queue of held orders, then the
     * branch's three, then the orders care trashed. Pending is the shared queue
     * anyone can claim; picked and served orders are only shown to the staff
     * member who picked them; a held order is only visible here.
     */
    public function index(Request $request)
    {
        $branchId = (int) auth()->user()->branch_id;
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveCareTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return view('customer-care.orders.index', [
            'orders' => $orders,
            'counts' => $this->workflow->counts($branchId, $userId),
            'pickers' => $this->workflow->pickerNames($orders),
            'tab' => $tab,
            'tabs' => OrderWorkflowService::CARE_TABS,
            'tabRoute' => 'customer-care.orders.index',
            'nameRoute' => 'customer-care.orders.personal-name',
            'transitions' => OrderWorkflowService::TRANSITIONS + OrderWorkflowService::CARE_TRANSITIONS,
            'userId' => $userId,
            // Loyal crowns next to the customers on this tab.
            'loyalIds' => $this->loyalIdsFor(
                collect($orders)->map(fn ($order) => (int) ($order->customer->id ?? 0))->all()
            ),
        ]);
    }

    public function show($orderId)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $order = $this->workflow->findForShow((int) $orderId, (int) auth()->user()->branch_id, $userId, true, true);

        if (!$order) {
            abort(404);
        }

        return view('customer-care.orders.show', [
            'order' => $order,
            'transitions' => OrderWorkflowService::TRANSITIONS + OrderWorkflowService::CARE_TRANSITIONS,
            'userId' => $userId,
            'nameRoute' => 'customer-care.orders.personal-name',
            'canName' => $this->workflow->isOwnedBy((array) $order, $userId),
            'isLoyal' => $this->loyalIdsFor([(int) ($order->customer->id ?? 0)]) !== [],
        ]);
    }

    /**
     * Move an order forward. Only picked and served are accepted: an order
     * never goes back to pending, so a crafted request cannot un-claim work
     * that has already been picked.
     */
    public function updateStatus(Request $request, $orderId)
    {
        // The status still has to be a real move forward, but the update note is
        // this role's to skip: customer care can pick or serve an order without
        // describing the step. Other roles keep the service's required-note rules.
        $rules = $this->workflow->statusChangeRules();
        $rules['note'] = 'nullable|string|max:2000';

        $request->validate($rules);

        $next = $request->status;
        $note = (string) ($request->note ?? '');

        if (!in_array($next, ['picked', 'served'], true)) {
            return back()->with('error', 'An order can only move to picked or served.');
        }

        $userId = auth()->user()->supabase_id ?? auth()->id();
        $branchId = (int) auth()->user()->branch_id;

        $result = $next === 'picked'
            ? $this->workflow->pick((int) $orderId, $branchId, $userId, $note)
            : $this->workflow->serve((int) $orderId, $branchId, $userId, $note);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Customer care's decision on a held order: allow it (pending) so the
     * branch can work it, or trash it (failed) so no branch list ever shows it.
     * Anything else is refused, exactly like the picked/served transitions.
     */
    public function decide(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,failed',
            'note' => 'nullable|string|max:2000',
        ]);

        $result = $this->workflow->decide(
            (int) $orderId,
            (int) auth()->user()->branch_id,
            auth()->user()->supabase_id ?? auth()->id(),
            $request->status,
            (string) ($request->note ?? '')
        );

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Save, edit or clear this staff member's personal name for the order.
     */
    public function personalName(Request $request, $orderId)
    {
        $request->validate([
            'personal_order_name' => 'nullable|string|max:' . OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $this->workflow->savePersonalName(
            (int) $orderId,
            (int) auth()->user()->branch_id,
            auth()->user()->supabase_id ?? auth()->id(),
            $request->personal_order_name
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return back()->with('success', $result['message']);
    }
}
