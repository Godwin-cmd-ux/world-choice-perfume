@extends('stock-manager.layouts.app')
@section('title', 'Product Stock')
@section('header', 'Product Stock')

@section('header-actions')
    <form method="GET" action="{{ route('stock-manager.product-stock') }}" class="flex items-center gap-2 mr-2">
        <input type="text" name="search" value="{{ request('search') }}"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            placeholder="Search by product or brand…">
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-2 rounded-lg text-sm">
            <i class="fas fa-search"></i>
        </button>
        @if(request('search'))
            <a href="{{ route('stock-manager.product-stock') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.product-stock.entry') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-plus mr-1"></i> Add Stock
        </a>
        <a href="{{ route('stock-manager.stock-transfers.create', ['type' => 'product']) }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-arrow-right-arrow-left mr-1"></i> Transfer Stock
        </a>
    @endif
    <a href="{{ route('stock-manager.product-stock-movements') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-exchange-alt mr-1"></i> Movements
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex justify-between items-center">
        <span class="text-sm text-gray-500">Total Stock Value:</span>
        <span class="text-lg font-bold text-emerald-700">TZS {{ number_format($totalValue) }}</span>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Product</th>
                <th class="text-right px-4">Quantity</th>
                <th class="text-right px-4">Unit Cost</th>
                <th class="text-right px-4">Selling Price</th>
                <th class="text-right px-4">Stock Value</th>
                <th class="text-left px-4">Category</th>
                <th class="text-left px-4">Last Received</th>
                <th class="text-right px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                    @php
                        $stockValue = ($stock->quantity ?? 0) * ($stock->selling_price ?? 0);
                        $lowQty = ($stock->quantity <= 5);
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $stock->product->name }}</td>
                        <td class="px-4 text-right">
                            <span class="{{ $lowQty ? 'text-red-600 font-bold' : '' }}">{{ $stock->quantity }}</span>
                        </td>
                        <td class="px-4 text-right text-gray-500">TZS {{ number_format($stock->buying_cost ?? 0) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($stock->selling_price) }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($stockValue) }}</td>
                        <td class="px-4">@if(!empty($stock->category))<span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800">{{ $stock->category }}</span>@else <span class="text-gray-400">—</span> @endif</td>
                        <td class="px-4 text-gray-500">{{ $stock->date_received ? \Carbon\Carbon::parse($stock->date_received)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '-' }}</td>
                        <td class="px-4 text-right">
                            @if(!($inCrossBranch ?? false))
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                        class="edit-stock-btn inline-flex items-center gap-1 text-white px-2.5 py-1.5 rounded text-xs font-medium hover:opacity-90"
                                        style="background-color: #F89A1E;"
                                        data-stock-id="{{ $stock->id }}"
                                        data-product="{{ $stock->product->name }}"
                                        data-quantity="{{ $stock->quantity }}"
                                        data-price="{{ $stock->selling_price ?? 0 }}">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('stock-manager.product-stock.destroy', $stock->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs" title="Delete" data-confirm="Delete stock record for {{ $stock->product->name }}?">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Read only</span>
                            @endif
                        </td>
                    </tr>
                    @php
                        $varieties = $varietyMap[$stock->product_id] ?? [];
                    @endphp
                    @if(($stock->category ?? '') === 'Oil Fragrance' && count($varieties) > 0)
                        <tr class="border-t bg-gray-50/60">
                            <td colspan="8" class="px-12 py-2">
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                    <i class="fas fa-wine-bottle mr-1"></i>Bottled varieties — {{ $stock->product->name }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($varieties as $volume => $variants)
                                        @foreach($variants as $variantKey => $qty)
                                            @if($qty > 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] bg-white border border-gray-200">
                                                    {{ $volume }}ml &middot; {{ str_replace('_', ' ', $variantKey) }}
                                                    <span class="font-semibold text-emerald-700">&times; {{ $qty }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-gray-400">No stock records yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Stock Modal -->
<div id="editStockModal" class="fixed inset-0 z-50 bg-black/50 hidden" data-update-url="{{ route('stock-manager.product-stock.update', ['stock' => '__STOCK__']) }}">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-boxes-stacked text-emerald-600"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Edit Stock</h3>
                        <p id="editStockProduct" class="text-xs text-gray-500 truncate max-w-[16rem]"></p>
                    </div>
                </div>
                <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="editStockForm" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="quantity" id="editStockQuantity" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 no-spinner">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (TZS)</label>
                    <input type="text" inputmode="decimal" name="selling_price" id="editStockPrice" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 no-spinner">
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-500">New stock value:</span>
                    <span id="editStockPreview" class="text-sm font-bold text-emerald-700">TZS 0</span>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" style="background-color: #F89A1E;" class="px-5 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .no-spinner::-webkit-outer-spin-button,
    .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .no-spinner { -moz-appearance: textfield; appearance: textfield; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('editStockModal');
        if (!modal) return;

        var form = document.getElementById('editStockForm');
        var productEl = document.getElementById('editStockProduct');
        var qtyInput = document.getElementById('editStockQuantity');
        var priceInput = document.getElementById('editStockPrice');
        var preview = document.getElementById('editStockPreview');
        var updateUrl = modal.getAttribute('data-update-url');

        function formatTzs(value) {
            var n = parseFloat(value) || 0;
            return 'TZS ' + n.toLocaleString('en-US', { maximumFractionDigits: 2 });
        }

        function refreshPreview() {
            preview.textContent = formatTzs((parseFloat(qtyInput.value) || 0) * (parseFloat(priceInput.value) || 0));
        }

        function openModal(btn) {
            form.setAttribute('action', updateUrl.replace('__STOCK__', btn.getAttribute('data-stock-id')));
            productEl.textContent = btn.getAttribute('data-product') || '';
            qtyInput.value = btn.getAttribute('data-quantity') || '0';
            priceInput.value = btn.getAttribute('data-price') || '0';
            refreshPreview();
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            qtyInput.focus();
            qtyInput.select();
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.edit-stock-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { openModal(btn); });
        });

        modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        // Strip anything that isn't a digit / decimal point as the user types.
        qtyInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^\d]/g, '');
            refreshPreview();
        });
        priceInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^\d.]/g, '');
            refreshPreview();
        });
    })();
</script>
@endpush
