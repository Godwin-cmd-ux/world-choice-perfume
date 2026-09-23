@extends('stock-manager.layouts.app')
@section('title', 'Lost / Broken Report')
@section('header', 'Lost / Broken Report')
@section('header-subtitle', 'Mandatory report for returned stock — forwarded to the Super Admin with the transfer officer attached')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-signature text-red-600"></i>
                </span>
                <div>
                    <p class="font-semibold text-gray-800">{{ $itemLabel }}</p>
                    <p class="text-xs text-gray-500">
                        Transfer <span class="font-mono font-semibold text-emerald-700">{{ $transfer->transfer_number ?? '' }}</span>
                        · {{ $item->quantity }} unit{{ $item->quantity == 1 ? '' : 's' }}
                        · rejected by {{ $toBranchName }}
                    </p>
                </div>
            </div>
        </div>

        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/60 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400 mb-0.5">Rejection reason (by receiving branch)</p>
                <p class="text-gray-700">{{ $item->return_reason ?? 'No reason given' }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400 mb-0.5">Transfer officer (registered on transfer)</p>
                @if($transfer->officer_name)
                    <p class="text-gray-800">{{ $transfer->officer_name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $transfer->officer_phone }}{{ $transfer->officer_id ? ' · ID '.$transfer->officer_id : '' }}
                    </p>
                @else
                    <p class="text-gray-500 italic">Not recorded</p>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.returned-stock.damage-report.store', $item->id) }}" class="p-5 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">What happened to this stock? <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 border-2 border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-red-300 transition has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                        <input type="radio" name="damage_type" value="lost" {{ old('damage_type') === 'lost' ? 'checked' : '' }} required class="w-4 h-4 text-red-600 focus:ring-red-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800"><i class="fas fa-circle-question mr-1 text-red-500"></i> Lost</span>
                            <span class="block text-xs text-gray-500">Completely missing — cannot be recovered</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 border-2 border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-orange-300 transition has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                        <input type="radio" name="damage_type" value="broken" {{ old('damage_type') === 'broken' ? 'checked' : '' }} required class="w-4 h-4 text-orange-600 focus:ring-orange-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800"><i class="fas fa-heart-crack mr-1 text-orange-500"></i> Broken</span>
                            <span class="block text-xs text-gray-500">Damaged in transit or on arrival</span>
                        </span>
                    </label>
                </div>
                @error('damage_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Your explanation <span class="text-red-500">*</span></label>
                <textarea name="damage_reason" rows="4" required minlength="3" maxlength="500"
                          placeholder="Explain in detail what happened — where the stock was last seen, who handled it, and any evidence. The Super Admin will use this to take physical action."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm">{{ old('damage_reason') }}</textarea>
                @error('damage_reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                <strong>This report is mandatory</strong> — the item cannot be re-sent or written off until it is filed.
                The quantity is deducted from {{ $fromBranchName }} stock, and the Super Admin is notified immediately with the
                transfer officer's details for physical follow-up. False declarations may be investigated.
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        data-confirm="File this report and notify the Super Admin? The stock ({{ $item->quantity }} x '{{ $itemLabel }}') will be written off."
                        class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">
                    <i class="fas fa-paper-plane"></i> Submit report
                </button>
                <a href="{{ route('stock-manager.returned-stock.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
