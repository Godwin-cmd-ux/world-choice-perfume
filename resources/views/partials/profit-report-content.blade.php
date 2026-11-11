{{-- Date filter --}}
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" action="{{ $action }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        <span class="text-xs text-gray-500 pb-2">{{ $rangeLabel }}</span>
    </form>
</div>

{{-- Sales / expenses / profit cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill-wave text-green-700"></i></div>
            <div>
                <p class="text-lg font-bold truncate">TZS {{ number_format($summary['revenue']) }}</p>
                <p class="text-xs text-gray-500">Sales</p>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">{{ number_format($summary['transactions']) }} transaction{{ $summary['transactions'] === 1 ? '' : 's' }} · {{ number_format($summary['items_sold']) }} item{{ $summary['items_sold'] === 1 ? '' : 's' }} sold</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-file-invoice-dollar text-red-700"></i></div>
            <div>
                <p class="text-lg font-bold truncate">TZS {{ number_format($summary['expenses']) }}</p>
                <p class="text-xs text-gray-500">Expenses</p>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">Recorded in the selected period</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-coins text-amber-700"></i></div>
            <div>
                <p class="text-lg font-bold truncate">TZS {{ number_format($summary['gross_profit']) }}</p>
                <p class="text-xs text-gray-500">Gross Profit</p>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">Sales − cost of goods sold (TZS {{ number_format($summary['cogs']) }})</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 {{ $summary['net_profit'] >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-full flex items-center justify-center"><i class="fas fa-sack-dollar {{ $summary['net_profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}"></i></div>
            <div>
                <p class="text-lg font-bold truncate {{ $summary['net_profit'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">TZS {{ number_format($summary['net_profit']) }}</p>
                <p class="text-xs text-gray-500">Net Profit</p>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">Gross profit − expenses</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-percent text-blue-700"></i></div>
            <div>
                <p class="text-lg font-bold truncate">{{ number_format($summary['margin'], 1) }}%</p>
                <p class="text-xs text-gray-500">Gross Margin</p>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">Gross profit ÷ sales</p>
    </div>
</div>

{{-- Current product profit summary --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Products</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($stockSummary['products']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Units in Stock</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($stockSummary['units']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Stock Cost Value</p>
        <p class="text-xl font-bold text-gray-800 mt-1 truncate">TZS {{ number_format($stockSummary['stock_cost']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Stock Selling Value</p>
        <p class="text-xl font-bold text-gray-800 mt-1 truncate">TZS {{ number_format($stockSummary['stock_value']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Potential Profit</p>
        <p class="text-xl font-bold text-emerald-700 mt-1 truncate">TZS {{ number_format($stockSummary['potential_profit']) }}</p>
        <p class="text-[10px] text-gray-400 mt-1">{{ number_format($stockSummary['margin'], 1) }}% margin</p>
    </div>
</div>

{{-- Product profit table --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-800">Product Profit</h3>
            <p class="text-xs text-gray-400">Unit cost, selling price and profit for every product currently in stock</p>
        </div>
        <span class="text-[11px] text-gray-400">Profit = selling price − unit cost</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Product</th>
                    @if($showBranch ?? false)
                        <th class="text-left px-4">Branch</th>
                    @endif
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-right px-4">Unit Cost</th>
                    <th class="text-right px-4">Selling Price</th>
                    <th class="text-right px-4">Profit / Unit</th>
                    <th class="text-right px-4">Stock Value</th>
                    <th class="text-right px-4">Potential Profit</th>
                    <th class="text-right px-4">Margin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <span class="font-medium block">{{ $row['product'] }}</span>
                            @if(!empty($row['brand']) || !empty($row['category']))
                                <span class="text-[11px] text-gray-400">{{ $row['brand'] ?? '' }}{{ !empty($row['brand']) && !empty($row['category']) ? ' · ' : '' }}{{ $row['category'] ?? '' }}</span>
                            @endif
                        </td>
                        @if($showBranch ?? false)
                            <td class="px-4 text-gray-500">{{ $row['branch'] }}</td>
                        @endif
                        <td class="px-4 text-right">{{ number_format($row['quantity']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($row['unit_cost']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($row['selling_price']) }}</td>
                        <td class="px-4 text-right font-medium {{ $row['unit_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">TZS {{ number_format($row['unit_profit']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($row['stock_value']) }}</td>
                        <td class="px-4 text-right font-semibold {{ $row['potential_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">TZS {{ number_format($row['potential_profit']) }}</td>
                        <td class="px-4 text-right">{{ number_format($row['margin'], 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($showBranch ?? false) ? 9 : 8 }}" class="py-8 text-center text-gray-400">No product stock yet</td></tr>
                @endforelse
            </tbody>
            @if(count($rows))
                <tfoot class="bg-gray-50 border-t font-semibold">
                    <tr>
                        <td colspan="{{ ($showBranch ?? false) ? 2 : 1 }}" class="py-3 px-4 text-right">Total</td>
                        <td class="px-4 text-right">{{ number_format($stockSummary['units']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($stockSummary['stock_cost']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($stockSummary['stock_value']) }}</td>
                        <td class="px-4 text-right">—</td>
                        <td class="px-4 text-right">TZS {{ number_format($stockSummary['stock_value']) }}</td>
                        <td class="px-4 text-right text-emerald-700">TZS {{ number_format($stockSummary['potential_profit']) }}</td>
                        <td class="px-4 text-right">{{ number_format($stockSummary['margin'], 1) }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
