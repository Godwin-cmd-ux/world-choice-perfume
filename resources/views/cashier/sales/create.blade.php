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
                    <input type="text" name="customer_name" id="newCustomerName" placeholder="New customer name" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    <input type="text" name="customer_phone" id="newCustomerPhone" placeholder="Phone (optional)" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <button type="button" onclick="toggleNewCustomer()" id="newCustomerBtn" class="w-full text-sm text-amber-700 hover:underline">
                    <i class="fas fa-plus mr-1"></i> New Customer
                </button>
                <p class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> Optional. Skip for walk-in sales.</p>
            </div>

            <!-- Supplier -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3"><i class="fas fa-tag mr-1"></i> Sale Details</h3>
                <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Supplier (optional)" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <!-- Right Column: Products + Payment -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Product Selection -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold"><i class="fas fa-box mr-1"></i> Products</h3>
                    <input type="text" id="productSearch" placeholder="Search products..." class="w-48 px-3 py-1.5 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="item-row flex gap-2 items-start">
                        <div class="flex-1 space-y-1">
                            <select name="items[0][product_id]" required class="w-full px-3 py-2 border rounded-lg text-sm product-select">
                                <option value="">-- Select Product --</option>
                                @foreach($products as $stock)
                                    <option value="{{ $stock->product_id }}"
                                        data-price="{{ $stock->selling_price }}"
                                        data-stock="{{ $stock->quantity }}"
                                        data-name="{{ $stock->product->name }}">
                                        {{ $stock->product->name }} - TZS {{ number_format($stock->selling_price) }} ({{ $stock->quantity }} in stock)
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="items[0][custom_price]" step="0.01" min="0" placeholder="Custom price"
                                class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-amber-500 wholesale-price-input hidden"
                                oninput="calculateTotal()">
                        </div>
                        <input type="number" name="items[0][quantity]" value="1" min="1" class="w-20 px-3 py-2 border rounded-lg text-sm text-center qty-input">
                        <span class="line-total font-medium text-sm w-32 text-right pt-2">TZS 0</span>
                        <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 px-2 pt-2"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <button type="button" onclick="addRow()" class="mt-3 text-amber-700 hover:underline text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Another Product
                </button>

                <div class="mt-4 pt-4 border-t flex justify-between items-center">
                    <span class="text-lg font-semibold">Total:</span>
                    <span id="grand-total" class="text-2xl font-bold text-amber-700">TZS 0</span>
                </div>
            </div>

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
<script>
let rowIndex = 1;
let paymentRowIndex = 1;

// ===================== SALE TYPE =====================
function setSaleType(type) {
    document.getElementById('sale_type').value = type;
    document.getElementById('tab-retail').className = type === 'retail'
        ? 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-amber-600 text-white shadow'
        : 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200';
    document.getElementById('tab-wholesale').className = type === 'wholesale'
        ? 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-amber-600 text-white shadow'
        : 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200';
    document.getElementById('sale-type-hint').innerHTML = type === 'wholesale'
        ? '<i class="fas fa-info-circle mr-1"></i> Custom prices allowed'
        : '<i class="fas fa-info-circle mr-1"></i> Fixed selling prices';

    // Show/hide custom price inputs
    document.querySelectorAll('.wholesale-price-input').forEach(el => {
        el.classList.toggle('hidden', type !== 'wholesale');
    });
    calculateTotal();
}

// ===================== CUSTOMER SEARCH =====================
const customerSearch = document.getElementById('customerSearch');
const customerResults = document.getElementById('customerResults');
let searchTimeout;

customerSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { customerResults.classList.add('hidden'); return; }
    searchTimeout = setTimeout(() => {
        fetch(`{{ route('api.customers.search') }}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    customerResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No customers found. <button type="button" onclick="createCustomerFromSearch()" class="text-amber-700 hover:underline">Create new</button></div>';
                } else {
                    customerResults.innerHTML = data.map(c =>
                        `<div class="px-3 py-2 text-sm hover:bg-amber-50 cursor-pointer border-b last:border-0" onclick="selectCustomer(${c.id}, '${(c.name||'').replace(/'/g,"\\'")}', '${(c.phone||'').replace(/'/g,"\\'")}')">
                            <span class="font-medium">${c.name || 'Unnamed'}</span>
                            ${c.phone ? `<span class="text-gray-500 ml-2">${c.phone}</span>` : ''}
                        </div>`
                    ).join('');
                }
                customerResults.classList.remove('hidden');
            });
    }, 300);
});

function selectCustomer(id, name, phone) {
    document.getElementById('customer_id').value = id;
    document.getElementById('selectedCustomerName').textContent = name;
    document.getElementById('selectedCustomerPhone').textContent = phone || '';
    document.getElementById('selectedCustomer').classList.remove('hidden');
    document.getElementById('newCustomerForm').classList.add('hidden');
    document.getElementById('newCustomerBtn').classList.add('hidden');
    document.getElementById('customerSearch').value = '';
    customerResults.classList.add('hidden');
    document.getElementById('newCustomerName').value = '';
    document.getElementById('newCustomerPhone').value = '';
}

function clearCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('selectedCustomer').classList.add('hidden');
    document.getElementById('newCustomerBtn').classList.remove('hidden');
    document.getElementById('newCustomerForm').classList.add('hidden');
}

function toggleNewCustomer() {
    const form = document.getElementById('newCustomerForm');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('selectedCustomer').classList.add('hidden');
        document.getElementById('customer_id').value = '';
    }
}

function createCustomerFromSearch() {
    const q = customerSearch.value.trim();
    document.getElementById('newCustomerForm').classList.remove('hidden');
    document.getElementById('newCustomerName').value = q;
    customerResults.classList.add('hidden');
    customerSearch.value = '';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults')) {
        customerResults.classList.add('hidden');
    }
});

// ===================== PRODUCT SEARCH =====================
document.getElementById('productSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-select').forEach(select => {
        Array.from(select.options).forEach(opt => {
            if (!opt.value) return;
            opt.style.display = opt.text.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});

// ===================== ITEMS =====================
function addRow() {
    const container = document.getElementById('items-container');
    const firstRow = container.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('select, input').forEach(el => {
        if (el.name) el.name = el.name.replace(/\d+/, rowIndex);
    });
    newRow.querySelector('.qty-input').value = 1;
    newRow.querySelector('.line-total').textContent = 'TZS 0';
    newRow.querySelector('.product-select').value = '';
    const customInput = newRow.querySelector('.wholesale-price-input');
    if (customInput) { customInput.value = ''; customInput.classList.toggle('hidden', document.getElementById('sale_type').value !== 'wholesale'); }
    container.appendChild(newRow);
    rowIndex++;
    bindEvents();
}

function removeRow(btn) {
    const container = document.getElementById('items-container');
    if (container.children.length > 1) {
        btn.closest('.item-row').remove();
        calculateTotal();
    }
}

function bindEvents() {
    document.querySelectorAll('.product-select').forEach(select => {
        select.onchange = function() { calculateTotal(); };
    });
    document.querySelectorAll('.qty-input').forEach(input => {
        input.oninput = function() { calculateTotal(); };
    });
}

function calculateTotal() {
    let total = 0;
    const isWholesale = document.getElementById('sale_type').value === 'wholesale';
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.product-select').selectedOptions[0]?.dataset?.price || 0);
        const qty = parseInt(row.querySelector('.qty-input').value || 0);
        const customInput = row.querySelector('.wholesale-price-input');
        let unitPrice = price;
        if (isWholesale && customInput && customInput.value) {
            unitPrice = parseFloat(customInput.value) || price;
        }
        const lineTotal = unitPrice * qty;
        row.querySelector('.line-total').textContent = 'TZS ' + lineTotal.toLocaleString();
        total += lineTotal;
    });
    document.getElementById('grand-total').textContent = 'TZS ' + total.toLocaleString();
    validatePayments();
}

// ===================== PAYMENT MODE =====================
function togglePaymentMode(mode) {
    document.querySelectorAll('.payment-mode-option').forEach(el => el.classList.remove('border-amber-500', 'bg-amber-50'));
    document.querySelector(`.payment-mode-option[data-mode="${mode}"]`).classList.add('border-amber-500', 'bg-amber-50');
    document.getElementById('singlePayment').classList.toggle('hidden', mode !== 'single');
    document.getElementById('multiPayment').classList.toggle('hidden', mode !== 'multi');
    if (mode === 'multi') validatePayments();
    else { document.getElementById('submitBtn').disabled = false; document.getElementById('submitBtn').classList.remove('opacity-50'); }
}

function addPaymentRow() {
    const container = document.getElementById('payment-rows');
    const firstRow = container.querySelector('.payment-row');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('select, input').forEach(el => { if (el.name) el.name = el.name.replace(/\d+/, paymentRowIndex); });
    newRow.querySelector('.payment-amount').value = '';
    container.appendChild(newRow);
    paymentRowIndex++;
}

function removePaymentRow(btn) {
    if (document.getElementById('payment-rows').children.length > 1) { btn.closest('.payment-row').remove(); validatePayments(); }
}

function validatePayments() {
    const mode = document.querySelector('input[name="payment_mode"]:checked')?.value;
    if (mode !== 'multi') return;
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.product-select').selectedOptions[0]?.dataset?.price || 0);
        const qty = parseInt(row.querySelector('.qty-input').value || 0);
        total += price * qty;
    });
    let paymentTotal = 0;
    document.querySelectorAll('.payment-amount').forEach(input => { paymentTotal += parseFloat(input.value || 0); });
    document.getElementById('payment-total').textContent = 'TZS ' + paymentTotal.toLocaleString();
    const mismatch = Math.abs(paymentTotal - total) > 0.01;
    document.getElementById('payment-mismatch').classList.toggle('hidden', !mismatch);
    document.getElementById('submitBtn').disabled = mismatch;
    document.getElementById('submitBtn').classList.toggle('opacity-50', mismatch);
}

bindEvents();
</script>
@endpush
@endsection
