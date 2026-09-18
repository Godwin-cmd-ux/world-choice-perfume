@extends('stock-manager.layouts.app')
@section('title', 'Transfer ' . $type_label)
@section('header', 'Transfer ' . $type_label)
@section('header-subtitle', 'From ' . $fromBranchName)

@php
    $crossMode = (new \App\Services\StockManagerScope())->inCrossBranchMode();

    // Normalize the controller options into flat select choices.
    $viewOptions = collect($options ?? [])->map(function ($o) use ($type) {
        if ($type === 'product') {
            return [
                'key' => (string) ($o['product_id'] ?? ''),
                'label' => ($o['name'] ?? 'Product') . ($o['brand'] ? " ({$o['brand']})" : ''),
                'available' => (int) ($o['available'] ?? 0),
                'product_id' => (int) ($o['product_id'] ?? 0),
            ];
        }
        if ($type === 'bottle') {
            return [
                'key' => ($o['volume'] ?? '') . '|' . ($o['variant'] ?? ''),
                'label' => ($o['volume'] ?? '') . ' — ' . ($o['variant_label'] ?? ''),
                'available' => (int) ($o['available'] ?? 0),
                'volume' => $o['volume'] ?? '',
                'variant' => $o['variant'] ?? '',
            ];
        }
        if ($type === 'oil_fragrance') {
            return [
                'key' => ($o['name'] ?? '') . '|' . ($o['volume'] ?? ''),
                'label' => ($o['name'] ?? '') . ($o['volume'] !== '' ? " ({$o['volume']}ml)" : ''),
                'available' => (int) ($o['available'] ?? 0),
                'name' => $o['name'] ?? '',
                'volume' => (string) ($o['volume'] ?? ''),
            ];
        }
        return [
            'key' => ($o['type'] ?? '') . '|' . ($o['color'] ?? ''),
            'label' => ucfirst(str_replace('_', ' ', $o['type'] ?? '')) . ' — ' . ucfirst($o['color'] ?? ''),
            'available' => (int) ($o['available'] ?? 0),
            'type' => $o['type'] ?? '',
            'color' => $o['color'] ?? '',
        ];
    })->values()->all();
@endphp

@section('content')
@if($crossMode)
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">
        <i class="fas fa-lock text-3xl mb-3 block"></i>
        <p>Transfers are read-only while monitoring another branch. Exit the branch to create a transfer.</p>
    </div>
@else
<form method="POST" action="{{ route('stock-manager.stock-transfers.store') }}" id="transfer-form">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">

    @if(count($branches) === 0)
        <div class="bg-white rounded-xl shadow p-10 text-center">
            <i class="fas fa-university text-3xl mb-3 block text-gray-300"></i>
            <p class="text-gray-500">No other active branches are available to receive this type of stock.</p>
        </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Transfer details --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-route mr-2 text-emerald-600"></i>Transfer Details</h3>

                <label class="block text-sm font-medium text-gray-700 mb-1">To Branch *</label>
                <select name="to_branch_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                    <option value="">Select branch…</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('to_branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('to_branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-user-tie mr-2 text-emerald-600"></i>Officer (recipient)</h3>

                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="officer_name" value="{{ old('officer_name') }}" required maxlength="191"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. John Mwakyusa">

                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input type="text" name="officer_phone" value="{{ old('officer_phone') }}" required maxlength="64"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. 0712 345 678">

                <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                <input type="text" name="officer_id" value="{{ old('officer_id') }}" maxlength="64"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Optional e.g. NIDA/2025/12345">

                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                <textarea name="note" rows="3" maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Optional note for the recipient branch">{{ old('note') }}</textarea>
            </div>
        </div>

        {{-- Items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800"><i class="fas fa-boxes-stacked mr-2 text-emerald-600"></i>Items</h3>
                    <button type="button" onclick="addRow()" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4">Item</th>
                                <th class="text-left px-4">Available</th>
                                <th class="text-right px-4 w-28">Quantity</th>
                                <th class="w-10 px-2"></th>
                            </tr>
                        </thead>
                        <tbody id="transfer-items"></tbody>
                    </table>
                </div>
                <p id="empty-hint" class="text-center text-gray-400 py-8">
                    <i class="fas fa-box-open text-3xl mb-2 block"></i>
                    No items added yet — click <strong>Add Item</strong> to include stock for this transfer.
                </p>
                <p id="quantity-hint" class="text-xs text-gray-500 mt-3"></p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Total transfer quantity</p>
                    <p id="total-qty" class="text-2xl font-bold text-emerald-700">0</p>
                </div>
                <button type="submit" disabled id="submit-btn" class="disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg text-sm font-semibold hover:opacity-90"
                    style="background-color: #F89A1E;">
                    <i class="fas fa-paper-plane mr-1"></i> Create Transfer
                </button>
            </div>
        </div>
    </div>
    @endif
</form>
@endif
@endsection

@push('scripts')
@if(!$crossMode && count($branches) > 0)
<script>
    const TYPE = @json($type);
    const OPTIONS = @json($viewOptions);

    const FIELD_NAMES = {
        product: ['product_id'],
        bottle: ['volume', 'variant'],
        oil_fragrance: ['name', 'volume'],
        bottle_accessories: ['type', 'color'],
    };

    const state = { rows: [], nextId: 1 };

    function optionBy(key) {
        return OPTIONS.find(o => String(o.key) === String(key)) || null;
    }

    function addRow() {
        state.rows.push({ id: state.nextId++, key: '', qty: '' });
        render();
    }

    function removeRow(id) {
        state.rows = state.rows.filter(r => r.id !== id);
        render();
    }

    function onSelect(id, selectEl) {
        const row = state.rows.find(r => r.id === id);
        if (row) {
            row.key = selectEl.value;
            render();
        }
    }

    function onQty(id, inputEl) {
        const row = state.rows.find(r => r.id === id);
        if (row) {
            row.qty = inputEl.value;
            updateTotals();
        }
    }

    function updateTotals() {
        const totalQty = document.getElementById('total-qty');
        const submitBtn = document.getElementById('submit-btn');
        const qtyHint = document.getElementById('quantity-hint');

        let total = 0;
        let qtyValid = true;

        state.rows.forEach(row => {
            const opt = optionBy(row.key);
            const available = opt ? opt.available : 0;
            const qty = parseInt(row.qty || '0', 10);
            if (qty > 0) total += qty;
            if (qty < 1 || qty > available) qtyValid = false;
        });

        totalQty.textContent = total.toLocaleString();
        submitBtn.disabled = state.rows.length === 0 || !qtyValid;
        qtyHint.textContent = state.rows.length > 0 && !qtyValid
            ? 'Set a valid quantity for every row (at least 1 and no more than the available stock).'
            : '';
    }

    function render() {
        const tb = document.getElementById('transfer-items');
        const hint = document.getElementById('empty-hint');

        tb.innerHTML = '';
        hint.style.display = state.rows.length ? 'none' : 'block';

        state.rows.forEach((row, index) => {
            const opt = optionBy(row.key);
            const available = opt ? opt.available : 0;

            const tr = document.createElement('tr');
            tr.className = 'border-t align-top';

            const tdSelect = document.createElement('td');
            tdSelect.className = 'py-3 px-4';
            const sel = document.createElement('select');
            sel.className = 'w-full border border-gray-300 rounded-lg text-sm px-2 py-2 bg-white focus:ring-2 focus:ring-emerald-500';
            sel.addEventListener('change', () => onSelect(row.id, sel));
            const blank = document.createElement('option');
            blank.value = '';
            blank.textContent = 'Select item…';
            sel.appendChild(blank);
            OPTIONS.forEach(o => {
                const op = document.createElement('option');
                op.value = o.key;
                op.textContent = o.label;
                if (String(o.key) === String(row.key)) op.selected = true;
                sel.appendChild(op);
            });
            tdSelect.appendChild(sel);
            // Hidden fields the controller resolves by type.
            if (opt) {
                (FIELD_NAMES[TYPE] || []).forEach(field => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `items[${index}][${field}]`;
                    hidden.value = opt[field] ?? '';
                    tr.appendChild(hidden);
                });
            }
            tr.appendChild(tdSelect);

            const tdAvail = document.createElement('td');
            tdAvail.className = 'py-3 px-4 text-sm text-gray-500';
            tdAvail.textContent = opt ? (available + ' available') : '—';
            if (opt && available === 0) tdAvail.className += ' text-red-500 font-medium';
            tr.appendChild(tdAvail);

            const tdQty = document.createElement('td');
            tdQty.className = 'py-3 px-4 text-right';
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.min = '1';
            qtyInput.max = opt ? available : 99999;
            qtyInput.value = row.qty;
            qtyInput.placeholder = 'Qty';
            qtyInput.className = 'w-24 text-right border border-gray-300 rounded-lg text-sm px-2 py-2 focus:ring-2 focus:ring-emerald-500';
            qtyInput.addEventListener('input', () => onQty(row.id, qtyInput));
            tdQty.appendChild(qtyInput);
            tr.appendChild(tdQty);

            const tdRemove = document.createElement('td');
            tdRemove.className = 'py-3 px-2 text-center';
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'text-red-500 hover:text-red-700 text-sm';
            rm.innerHTML = '<i class="fas fa-trash-alt"></i>';
            rm.addEventListener('click', () => removeRow(row.id));
            tdRemove.appendChild(rm);
            tr.appendChild(tdRemove);

            tb.appendChild(tr);
        });

        updateTotals();
    }

    if (OPTIONS.length) addRow();
    document.addEventListener('DOMContentLoaded', render);
</script>
@endif
@endpush