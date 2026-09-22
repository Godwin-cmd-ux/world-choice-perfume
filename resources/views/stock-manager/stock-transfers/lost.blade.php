@extends('stock-manager.layouts.app')
@section('title', 'Declare Lost Items')
@section('header', 'Declare Lost Items')
@section('header-subtitle', 'Report stock lost during a transfer — the admin is notified for cross-checking')

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.returns') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-undo mr-1"></i> Returned Items
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center gap-3">
            <span class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-file-signature text-red-600"></i>
            </span>
            <div>
                <p class="font-semibold text-gray-800">Lost items during transfer</p>
                <p class="text-xs text-gray-500">The quantity is deducted from {{ $branchName }} stock and the admin is notified.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.stock-transfers.declare-lost') }}" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transfer <span class="text-red-500">*</span></label>
                <select name="transfer_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">Select the transfer the loss occurred on…</option>
                    @forelse($transfers as $t)
                        <option value="{{ $t->id }}">{{ $t->transfer_number }} — {{ $t->stock_type_label }} → {{ $t->to_branch_name }} ({{ \Carbon\Carbon::parse($t->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }})</option>
                    @empty
                        <option value="" disabled>No outgoing transfers found</option>
                    @endforelse
                </select>
                @error('transfer_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Item description <span class="text-red-500">*</span></label>
                <input type="text" name="item" required maxlength="191"
                       placeholder="e.g. 100ml, Test perfume 50ml (With Box · With Logo · Yellow), 50ml With Box"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                <p class="text-xs text-gray-500 mt-1">For bottle stock use the volume, e.g. <code>100ml</code>. For product stock use the product name.</p>
                @error('item')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity lost <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    @error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">What happened? <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="4" required minlength="3" maxlength="500"
                          placeholder="e.g. officer reported bottles broken on the way, package arrived short..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"></textarea>
                @error('reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                <i class="fas fa-info-circle mr-1"></i>
                This is recorded in the audit trail and the admin is notified. False declarations may be investigated.
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Declare lost
                </button>
                <a href="{{ route('stock-manager.stock-transfers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
