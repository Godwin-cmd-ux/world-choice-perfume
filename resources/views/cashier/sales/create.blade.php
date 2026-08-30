@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
<form method="POST" action="{{ route('cashier.sales.store') }}" id="saleForm">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-4"><i class="fas fa-user mr-1"></i> Customer Info</h3>
                <div class="space-y-3">
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Customer Name" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Phone/WhatsApp Number" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="Email (optional)" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-4"><i class="fas fa-box mr-1"></i> Select Products</h3>

                <div id="items-container" class="space-y-3">
                    <div class="item-row flex gap-2 items-center">
                        <select name="items[0][product_id]" required class="flex-1 px-3 py-2 border rounded-lg text-sm product-select">
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
                        <input type="number" name="items[0][quantity]" value="1" min="1" class="w-20 px-3 py-2 border rounded-lg text-sm text-center qty-input">
                        <span class="line-total font-medium text-sm w-32 text-right">TZS 0</span>
                        <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 px-2"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <button type="button" onclick="addRow()" class="mt-3 text-amber-700 hover:underline text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Another Product
                </button>

                <div class="mt-6 pt-4 border-t flex justify-between items-center">
                    <span class="text-lg font-semibold">Total:</span>
                    <span id="grand-total" class="text-2xl font-bold text-amber-700">TZS 0</span>
                </div>

                <button type="submit" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition">
                    <i class="fas fa-check-circle mr-1"></i> Complete Sale
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
let rowIndex = 1;

function addRow() {
    const container = document.getElementById('items-container');
    const firstRow = container.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);

    // Update names
    newRow.querySelectorAll('select, input').forEach(el => {
        if (el.name) el.name = el.name.replace(/\d+/, rowIndex);
    });
    newRow.querySelector('.qty-input').value = 1;
    newRow.querySelector('.line-total').textContent = 'TZS 0';
    newRow.querySelector('.product-select').value = '';

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
        select.onchange = function() {
            const price = parseFloat(this.selectedOptions[0]?.dataset?.price || 0);
            const qty = parseInt(this.closest('.item-row').querySelector('.qty-input').value || 0);
            this.closest('.item-row').querySelector('.line-total').textContent = 'TZS ' + (price * qty).toLocaleString();
            calculateTotal();
        };
    });

    document.querySelectorAll('.qty-input').forEach(input => {
        input.oninput = function() {
            const row = this.closest('.item-row');
            const price = parseFloat(row.querySelector('.product-select').selectedOptions[0]?.dataset?.price || 0);
            const qty = parseInt(this.value || 0);
            row.querySelector('.line-total').textContent = 'TZS ' + (price * qty).toLocaleString();
            calculateTotal();
        };
    });
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.product-select').selectedOptions[0]?.dataset?.price || 0);
        const qty = parseInt(row.querySelector('.qty-input').value || 0);
        total += price * qty;
    });
    document.getElementById('grand-total').textContent = 'TZS ' + total.toLocaleString();
}

bindEvents();
</script>
@endpush
@endsection
