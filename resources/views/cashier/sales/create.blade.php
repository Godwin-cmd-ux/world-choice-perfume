@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
<form method="POST" action="{{ route('cashier.sales.store') }}" id="saleForm">
    @csrf
    <input type="hidden" name="customer_id" id="customer_id" value="">
    <input type="hidden" name="sale_type" id="sale_type" value="retail">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Sale Type Tabs -->
            <div class="bg-white rounded-xl shadow p-4">
                <h3 class="font-semibold mb-3 text-sm"><i class="fas fa-tags mr-1"></i> Sale Type</h3>
                <div class="flex gap-2">
                    <button type="button" onclick="setSaleType('retail')" id="tab-retail"
                        class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-amber-600 text-white shadow">
                        <i class="fas fa-store mr-1"></i> Retail
                    </button>
                    <button type="button" onclick="setSaleType('wholesale')" id="tab-wholesale"
                        class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200">
                        <i class="fas fa-boxes mr-1"></i> Wholesale
                    </button>
                </div>
                <p id="sale-type-hint" class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> Fixed selling prices</p>
            </div>

            <!-- Customer Search / Create -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3"><i class="fas fa-user mr-1"></i> Customer</h3>

                <div class="relative mb-3">
                    <input type="text" id="customerSearch" placeholder="Search by name or phone..."
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500" autocomplete="off">
                    <div id="customerResults" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
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

                <div id="newCustomerForm" class="hidden space-y-2 mb-3">
                    <input type="text" name="customer_name" id="newCustomerName" placeholder="New customer name" autocomplete="off" oninput="document.getElementById('customer_id').value='';" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    <input type="text" name="customer_phone" id="newCustomerPhone" placeholder="Phone (optional)" autocomplete="off" oninput="document.getElementById('customer_id').value='';" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <button type="button" onclick="toggleNewCustomer()" id="newCustomerBtn" class="w-full text-sm text-amber-700 hover:underline">
                    <i class="fas fa-plus mr-1"></i> New Customer
                </button>
                <p class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> Optional. Skip for walk-in sales.</p>
            </div>

            <!-- Sale Details -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3"><i class="fas fa-tag mr-1"></i> Sale Details</h3>
                <p class="text-xs text-gray-400"><i class="fas fa-info-circle mr-1"></i> Sale details are auto-recorded.</p>
            </div>
        </div>

        <!-- Right Column: Products + Payment -->
        <div class="lg:col-span-2 space-y-6">
            @include('partials.sale-products-card', ['accent' => 'amber'])

            <!-- Payment Mode -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-4"><i class="fas fa-credit-card mr-1"></i> Payment Mode</h3>

                <div class="flex gap-3 mb-4">
                    <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-mode-option border-amber-500 bg-amber-50" data-mode="single">
                        <input type="radio" name="payment_mode" value="single" checked onchange="togglePaymentMode('single')">
                        <span class="text-sm font-medium">Single Payment</span>
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-mode-option" data-mode="multi">
                        <input type="radio" name="payment_mode" value="multi" onchange="togglePaymentMode('multi')">
                        <span class="text-sm font-medium">Multi Payment</span>
                    </label>
                </div>

                <div id="singlePayment" class="space-y-3">
                    <select name="payments[0][method]" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
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
                    <button type="button" onclick="addPaymentRow()" class="text-amber-700 hover:underline text-sm"><i class="fas fa-plus mr-1"></i> Add Payment</button>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-sm text-gray-500">Payment Total:</span>
                        <span id="payment-total" class="font-bold text-sm">TZS 0</span>
                    </div>
                    <p id="payment-mismatch" class="hidden text-xs text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i> Payment total must equal the sale total.</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition" id="submitBtn">
                <i class="fas fa-check-circle mr-1"></i> Complete Sale
            </button>
        </div>
    </div>
</form>

@push('scripts')
@include('partials.sale-script', ['accent' => 'amber'])
@endpush
@endsection