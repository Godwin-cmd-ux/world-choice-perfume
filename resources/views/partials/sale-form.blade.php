{{-- Shared sale wizard: Sale type → Customer (skippable) → Products → Payment.
     One flow for cashier, seller, customer care, branch admin and stock manager,
     so every role is asked for the same things in the same order.

     Params: $formAction (POST target), $accent, $hasDiscount (stock manager),
     $backRoute (optional link back to the sales list). --}}
@php
    $accent = $accent ?? 'amber';
    $backRoute = $backRoute ?? null;
    $steps = [1 => 'Sale type', 2 => 'Customer', 3 => 'Products', 4 => 'Payment'];
@endphp

{{-- The step rail builds its accent classes at runtime, so every role's accent
     is written out literally here for Tailwind to compile. --}}
<div class="hidden bg-amber-600 bg-amber-100 text-amber-700 bg-cyan-600 bg-cyan-100 text-cyan-700 bg-emerald-600 bg-emerald-100 text-emerald-700"></div>

@if ($backRoute)
    <a href="{{ $backRoute }}" class="inline-block text-sm text-gray-500 hover:text-gray-700 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Back to Sales
    </a>
@endif

<form method="POST" action="{{ $formAction }}" id="saleForm" class="max-w-3xl mx-auto">
    @csrf
    <input type="hidden" name="customer_id" id="customer_id" value="">
    <input type="hidden" name="sale_type" id="sale_type" value="retail">

    {{-- Progress rail. Later steps unlock one at a time; finished ones stay
         clickable so a wrong choice can be corrected without losing the cart. --}}
    <ol class="bg-white rounded-xl shadow p-3 sm:p-4 flex items-center gap-1.5 sm:gap-2">
        @foreach ($steps as $n => $label)
            <li class="flex-1 min-w-0">
                <button type="button" data-step-go="{{ $n }}" onclick="goToStep({{ $n }})"
                    aria-label="Step {{ $n }}: {{ $label }}" aria-current="false"
                    class="w-full flex items-center gap-2 rounded-lg px-1.5 sm:px-2 py-1.5 transition">
                    <span class="sale-step-dot flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-400">{{ $n }}</span>
                    <span class="sale-step-label hidden sm:block truncate text-xs font-semibold text-gray-400">{{ $label }}</span>
                </button>
            </li>
        @endforeach
    </ol>

    <!-- Step 1: Sale type -->
    <section class="sale-step mt-6" data-step="1">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-1"><i class="fas fa-tags mr-1"></i> Sale Type</h3>
            <p class="text-xs text-gray-400 mb-4">Choose how this sale is priced. The product list follows your choice.</p>
            <div class="flex gap-2">
                <button type="button" onclick="setSaleType('retail')" id="tab-retail"
                    class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-{{ $accent }}-600 text-white shadow">
                    <i class="fas fa-store mr-1"></i> Retail
                </button>
                <button type="button" onclick="setSaleType('wholesale')" id="tab-wholesale"
                    class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-{{ $accent }}-100 hover:text-{{ $accent }}-700">
                    <i class="fas fa-boxes mr-1"></i> Wholesale
                </button>
            </div>
            <p id="sale-type-hint" class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> Custom prices allowed</p>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="goToStep(2)" style="background-color: #F89A1E;"
                class="px-6 py-2.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition">
                Continue <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
    </section>

    <!-- Step 2: Customer (optional) -->
    <section class="sale-step mt-6 hidden" data-step="2">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-1"><i class="fas fa-user mr-1"></i> Customer</h3>
            <p class="text-xs text-gray-400 mb-3">Search an existing customer or add a new one — or skip for a walk-in sale.</p>

            <div class="flex items-stretch gap-2 mb-3">
                <div class="relative flex-1">
                    <input type="text" id="customerSearch" placeholder="Search by name or phone..."
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-{{ $accent }}-500" autocomplete="off">
                    <div id="customerResults" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                </div>
                <button type="button" id="customerSearchBtn" aria-label="Search customers" title="Search customers"
                    style="background-color: #F89A1E;"
                    class="shrink-0 inline-flex items-center justify-center px-3.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div id="selectedCustomer" class="hidden mb-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-800" id="selectedCustomerName"></p>
                        <p class="text-xs text-green-600" id="selectedCustomerPhone"></p>
                    </div>
                    <button type="button" onclick="clearCustomer()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div id="newCustomerForm" class="hidden space-y-2 mb-3 p-3 border border-dashed rounded-lg">
                <p class="text-xs font-semibold text-gray-600">New customer</p>
                <div id="newCustomerError" class="hidden text-xs text-red-600"></div>
                <input type="text" name="customer_name" id="newCustomerName" placeholder="Customer name" autocomplete="off"
                    oninput="document.getElementById('customer_id').value='';"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-{{ $accent }}-500">
                <input type="text" name="customer_phone" id="newCustomerPhone" placeholder="Phone number, e.g. 0682 601154" autocomplete="off"
                    oninput="document.getElementById('customer_id').value='';"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-{{ $accent }}-500">
                <button type="button" id="saveCustomerBtn" onclick="saveNewCustomer()"
                    style="background-color: #F89A1E;"
                    class="w-full px-4 py-2 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition disabled:opacity-50">
                    <i class="fas fa-save mr-1"></i> Save Customer
                </button>
            </div>

            <button type="button" onclick="toggleNewCustomer()" id="newCustomerBtn" class="w-full px-4 py-2.5 rounded-lg border border-{{ $accent }}-500 text-{{ $accent }}-700 text-sm font-semibold hover:bg-{{ $accent }}-50 transition">
                <i class="fas fa-user-plus mr-1"></i> Add Customer
            </button>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <button type="button" onclick="skipCustomer()" class="text-sm text-gray-500 hover:text-gray-800 underline">
                Skip — walk-in customer
            </button>
            <div class="flex items-center gap-2 ml-auto">
                <button type="button" onclick="goToStep(1)" class="px-4 py-2.5 rounded-lg bg-gray-100 text-gray-600 text-sm font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
                <button type="button" onclick="goToStep(3)" style="background-color: #F89A1E;"
                    class="px-6 py-2.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition">
                    Continue <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Step 3: Products -->
    <section class="sale-step mt-6 hidden" data-step="3">
        @include('partials.sale-products-card', ['accent' => $accent])

        <div class="mt-4 flex items-center justify-between gap-2">
            <button type="button" onclick="goToStep(2)" class="px-4 py-2.5 rounded-lg bg-gray-100 text-gray-600 text-sm font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </button>
            <button type="button" onclick="goToStep(4)" style="background-color: #F89A1E;"
                class="px-6 py-2.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition">
                Continue to Payment <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
    </section>

    <!-- Step 4: Payment -->
    <section class="sale-step mt-6 hidden" data-step="4">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-4"><i class="fas fa-credit-card mr-1"></i> Payment Mode</h3>

            <div class="flex items-center justify-between mb-4 pb-3 border-b">
                <span class="text-sm text-gray-500">Amount due</span>
                <span id="payment-grand-total" class="text-lg font-bold text-{{ $accent }}-700">TZS 0</span>
            </div>

            <div class="flex gap-3 mb-4">
                <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-mode-option border-{{ $accent }}-500 bg-{{ $accent }}-50" data-mode="single">
                    <input type="radio" name="payment_mode" value="single" checked onchange="togglePaymentMode('single')">
                    <span class="text-sm font-medium">Single Payment</span>
                </label>
                <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-mode-option" data-mode="multi">
                    <input type="radio" name="payment_mode" value="multi" onchange="togglePaymentMode('multi')">
                    <span class="text-sm font-medium">Multi Payment</span>
                </label>
            </div>

            <div id="singlePayment" class="space-y-3">
                <select name="payments[0][method]" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-{{ $accent }}-500">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="mobile_payment">Mobile Payment</option>
                </select>
            </div>

            <div id="multiPayment" class="hidden space-y-3">
                <div id="payment-rows" class="space-y-2">
                    <div class="payment-row flex gap-2 items-center">
                        <select name="payments[0][method]" class="w-40 px-3 py-2 border rounded-lg text-sm">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                        <input type="number" name="payments[0][amount]" step="0.01" min="0" placeholder="Amount" class="flex-1 px-3 py-2 border rounded-lg text-sm payment-amount" oninput="validatePayments()">
                        <button type="button" onclick="removePaymentRow(this)" class="text-red-500 hover:text-red-700 px-2"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <button type="button" onclick="addPaymentRow()" class="text-{{ $accent }}-700 hover:underline text-sm"><i class="fas fa-plus mr-1"></i> Add Payment</button>
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-sm text-gray-500">Payment Total:</span>
                    <span id="payment-total" class="font-bold text-sm">TZS 0</span>
                </div>
                <p id="payment-mismatch" class="hidden text-xs text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i> Payment total must equal the sale total.</p>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-2">
            <button type="button" onclick="goToStep(3)" class="px-4 py-2.5 rounded-lg bg-gray-100 text-gray-600 text-sm font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </button>
            <button type="submit" style="background-color: #F89A1E;" class="px-6 py-2.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition" id="submitBtn">
                <i class="fas fa-check-circle mr-1"></i> Complete Sale
            </button>
        </div>
    </section>
</form>

@push('scripts')
@include('partials.sale-script', ['accent' => $accent, 'hasDiscount' => $hasDiscount ?? false])
@endpush
