@extends('stock-manager.layouts.app')
@section('title', 'Bottle Accessories Stock Out')
@section('header', 'Bottle Accessories — Stock Out')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.bottle-accessories.stock-out') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Accessory Type *</label>
                <select name="type" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Type</option>
                    <option value="straws" {{ old('type') === 'straws' ? 'selected' : '' }}>Straws</option>
                    <option value="bottlenecks" {{ old('type') === 'bottlenecks' ? 'selected' : '' }}>Bottle Necks</option>
                    <option value="bottle_tops" {{ old('type') === 'bottle_tops' ? 'selected' : '' }}>Bottle Tops</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Color *</label>
                <select name="color" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Color</option>
                    <option value="silver" {{ old('color') === 'silver' ? 'selected' : '' }}>Silver</option>
                    <option value="gold" {{ old('color') === 'gold' ? 'selected' : '' }}>Gold</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (Packets) *</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Counted in packets</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason" value="{{ old('reason') }}" placeholder="e.g. Used in production"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-minus mr-1"></i> Record Stock Out
                </button>
                <a href="{{ route('stock-manager.bottle-accessories.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Current Stock -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
        <h3 class="font-semibold text-gray-800 mb-4">Current Stock</h3>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-2 px-4">Type</th>
                    <th class="text-left px-4">Color</th>
                    <th class="text-right px-4">Packets</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accessories as $a)
                    <tr class="border-t">
                        <td class="py-2 px-4 font-medium capitalize">{{ str_replace('_', ' ', $a->type) }}</td>
                        <td class="px-4 capitalize">{{ $a->color }}</td>
                        <td class="px-4 text-right font-bold">{{ number_format($a->quantity) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400">No accessories in stock</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
