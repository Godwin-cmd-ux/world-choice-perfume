@extends('layouts.app')
@section('title', 'New Customer')
@section('header', 'New Customer')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('customer-care.customers.store') }}" class="bg-white rounded-xl shadow p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i> Duplicates are checked automatically — creating another customer with the same phone opens the existing record.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
            <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i> Leave empty to use the phone number.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-semibold">
                <i class="fas fa-save mr-1"></i> Save Customer
            </button>
            <a href="{{ route('customer-care.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Clients
            </a>
        </div>
    </form>
</div>
@endsection