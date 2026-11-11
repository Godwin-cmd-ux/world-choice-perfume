@extends('stock-manager.layouts.app')
@section('title', 'Returned Transfer Items')
@section('header', 'Returned Transfer Items')
@section('header-subtitle', 'Items rejected by the receiving branch — re-send or write off as lost')

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.lost-form') }}" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-file-signature mr-1"></i> Declare Lost Items
    </a>
    <a href="{{ route('stock-manager.stock-transfers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-arrow-left mr-1"></i> All Transfers
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <span class="text-sm text-gray-500">Returned items from {{ $branchName }} transfers</span>
        @if($rows->isNotEmpty())
            <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-medium">{{ $rows->count() }} item{{ $rows->count() === 1 ? '' : 's' }}</span>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Transfer #</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-left px-4">Item</th>
                    <th class="text-right px-4">Qty</th>
                    <th class="text-left px-4">Rejected by</th>
                    <th class="text-left px-4">Reason</th>
                    <th class="text-left px-4">State</th>
                    <th class="text-center px-4">View</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $state = $row->item->return_status ?? 'pending';
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-xs font-semibold text-emerald-700">{{ $row->transfer_number }}</td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ $row->stock_type_label }}</span>
                        </td>
                        <td class="px-4 font-medium text-gray-800">{{ $row->item_label }}</td>
                        <td class="px-4 text-right text-gray-600">{{ $row->item->quantity }}</td>
                        <td class="px-4 text-gray-600">{{ $row->to_branch_name }}</td>
                        <td class="px-4 text-gray-600 min-w-[200px] max-w-[320px]">
                            <span class="block whitespace-normal break-words" title="{{ $row->item->return_reason }}">{{ $row->item->return_reason ?: '—' }}</span>
                            <span class="block text-[11px] text-gray-400">{{ $row->returned_at ? \Carbon\Carbon::parse($row->returned_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '' }}</span>
                        </td>
                        <td class="px-4">
                            @if($state === 'pending')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Awaiting action</span>
                            @elseif($state === 'resent')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">Re-sent</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-600">Written off</span>
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('stock-manager.stock-transfers.show', $row->item->transfer_id) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50"
                               title="View transfer {{ $row->transfer_number }}" aria-label="View transfer {{ $row->transfer_number }}">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                        <td class="px-4 text-center whitespace-nowrap">
                            @if(!($inCrossBranch ?? false) && $state === 'pending')
                                <div class="inline-flex items-center gap-2">
                                    <form method="POST" action="{{ route('stock-manager.stock-transfers.resend-returned', $row->item->id) }}" class="inline"
                                          data-confirm="Re-send {{ $row->item->quantity }} x '{{ $row->item_label }}' to {{ $row->to_branch_name }}?">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium">
                                            <i class="fas fa-redo mr-1"></i> Re-send
                                        </button>
                                    </form>
                                    <details class="inline-block">
                                        <summary class="list-none cursor-pointer inline-flex items-center bg-gray-700 hover:bg-gray-800 text-white px-3 py-1.5 rounded-lg text-xs font-medium">
                                            <i class="fas fa-file-signature mr-1"></i> Lost
                                        </summary>
                                        {{-- The write-off form opens as a fixed overlay so the
                                             table's horizontal scroll can never clip it. --}}
                                        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="this.closest('details').removeAttribute('open')"></div>
                                            <div class="relative w-full max-w-sm bg-white border border-gray-200 rounded-2xl shadow-2xl p-5 text-left whitespace-normal">
                                                <form method="POST" action="{{ route('stock-manager.stock-transfers.write-off-returned', $row->item->id) }}">
                                                    @csrf
                                                    <p class="text-sm font-semibold text-gray-800 mb-1">Write off as lost</p>
                                                    <p class="text-xs text-gray-500 mb-3">{{ $row->item->quantity }} x '{{ $row->item_label }}'</p>
                                                    <label class="block text-xs font-semibold text-gray-700 mb-1">What happened to it?</label>
                                                    <textarea name="loss_reason" rows="3" required minlength="3" maxlength="500" placeholder="e.g. bottles broken in transit, seized by officer..."
                                                              class="w-full text-sm border-gray-300 rounded-lg focus:ring-gray-500 focus:border-gray-500"></textarea>
                                                    <div class="flex gap-2 mt-3">
                                                        <button type="button" onclick="this.closest('details').removeAttribute('open')"
                                                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" data-confirm="Write off {{ $row->item->quantity }} x '{{ $row->item_label }}' as lost?"
                                                                class="flex-1 bg-gray-800 hover:bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium">
                                                            Confirm
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            @elseif($state === 'resent')
                                <span class="text-xs text-gray-400">—</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-gray-400">
                            <i class="fas fa-undo text-3xl mb-2 block"></i>
                            No returned items. Items rejected by receiving branches will appear here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
