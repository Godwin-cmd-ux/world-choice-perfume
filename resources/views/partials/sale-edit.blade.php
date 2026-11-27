@extends($layout ?? 'layouts.app')
@section('title', 'Edit Sale')
@section('header', 'Edit Sale')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="mb-5 border-b pb-4">
            <p class="text-sm text-gray-500">Sale <span class="font-semibold text-gray-800">{{ $sale->sale_number }}</span></p>
            <p class="text-xs text-gray-400 mt-1">
                Completed {{ \Carbon\Carbon::parse($sale->created_at)->setTimezone(\App\Support\BusinessDay::TIMEZONE)->format('M d, Y H:i') }}.
                Only sales completed today can be corrected. Items and stock are unchanged here; use Delete to undo a sale.
            </p>
        </div>

        <form method="POST" action="{{ route($routes['update'], $sale->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="sale-customer" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <select id="sale-customer" name="customer_id" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('customer_id') border-red-500 @enderror">
                        <option value="">Walk-in / unchanged</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id', $sale->customer_id ?? '') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}@if(!empty($customer->phone)) · {{ $customer->phone }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sale-payment-method" class="block text-sm font-medium text-gray-700 mb-1">Payment method *</label>
                    <select id="sale-payment-method" name="payment_method" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('payment_method') border-red-500 @enderror">
                        @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'mobile_payment' => 'Mobile Payment'] as $value => $label)
                            <option value="{{ $value }}" {{ old('payment_method', $sale->payment_method ?? 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sale-payment-summary" class="block text-sm font-medium text-gray-700 mb-1">Payment summary</label>
                    <input id="sale-payment-summary" type="text" name="payment_summary" maxlength="500"
                           value="{{ old('payment_summary', $sale->payment_summary ?? '') }}"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('payment_summary') border-red-500 @enderror"
                           placeholder="Leave blank to rebuild from the method and total">
                    @error('payment_summary') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="sale-subtotal" class="block text-sm font-medium text-gray-700 mb-1">Sale amount (TZS) *</label>
                        <input id="sale-subtotal" type="number" step="0.01" min="0" name="subtotal" required
                               value="{{ old('subtotal', $sale->subtotal ?? 0) }}"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('subtotal') border-red-500 @enderror">
                        @error('subtotal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sale-discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (TZS)</label>
                        <input id="sale-discount" type="number" step="0.01" min="0" name="discount"
                               value="{{ old('discount', $sale->discount ?? 0) }}"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('discount') border-red-500 @enderror">
                        @error('discount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="sale-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="sale-notes" name="notes" rows="3" maxlength="2000"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('notes') border-red-500 @enderror"
                              placeholder="Reason for the correction">{{ old('notes', $sale->notes ?? '') }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-6">
                <a href="{{ route($routes['index']) }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" style="background-color: #F89A1E;" class="px-6 py-2 hover:opacity-90 text-white rounded-lg font-medium">
                    <i class="fas fa-save mr-1"></i> Update Sale
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
