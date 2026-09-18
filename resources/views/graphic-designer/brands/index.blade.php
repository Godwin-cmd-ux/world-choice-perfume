@extends('layouts.app')
@section('title', 'Brands')
@section('header', 'Brands')
@section('header-actions')
    <div class="flex items-center gap-3 text-xs text-gray-500">
        <span class="flex items-center gap-1"><i class="fas fa-crown text-purple-500"></i> {{ $countsUi['total'] }} total</span>
        <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> {{ $countsUi['active'] }} active</span>
        <span class="flex items-center gap-1"><i class="fas fa-image text-amber-500"></i> {{ $countsUi['with_logo'] }} with logo</span>
    </div>
    <a href="{{ route('graphic-designer.brands.create') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> New Brand
    </a>
@endsection
@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Logo</th>
                    <th class="text-left px-4">Brand</th>
                    <th class="text-center px-4">Products</th>
                    <th class="text-center px-4">Status</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($brand->logo_url)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                            @else
                                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                                    {{ strtoupper(substr($brand->name, 0, 2)) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 font-medium">
                            {{ $brand->name }}
                            <div class="text-xs text-gray-500">Added {{ $brand->created_at ? \Carbon\Carbon::parse($brand->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</div>
                        </td>
                        <td class="px-4 text-center text-gray-600">{{ $brand->product_count }}</td>
                        <td class="px-4 text-center">
                            @if($brand->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('graphic-designer.brands.edit', $brand->id) }}" class="text-purple-600 hover:text-purple-800"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('graphic-designer.brands.destroy', $brand->id) }}" class="inline" data-confirm="Delete this brand?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 {{ $brand->product_count > 0 ? 'opacity-40 pointer-events-none' : '' }}" title="{{ $brand->product_count > 0 ? 'Brand in use — cannot delete' : 'Delete' }}"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-gray-400"><i class="fas fa-crown text-3xl mb-2 block"></i>No brands yet. Add your first brand.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection