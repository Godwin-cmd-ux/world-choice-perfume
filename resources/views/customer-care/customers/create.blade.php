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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
            {{-- Assign to: opens the branch list and puts this client on that
                 branch's customers list straight away. --}}
            <button type="button" onclick="toggleBranchPicker()" id="assignBranchButton"
                class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-amber-500">
                <i class="fas fa-store text-gray-400"></i>
                <span id="assignBranchLabel">Assign to</span>
                <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
            </button>
            <div id="branchPicker" class="hidden mt-2">
                <select name="branch_id" id="branch_id" onchange="showAssignedBranch()"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    <option value="">Select a branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i> The client joins this branch's list of customers.</p>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" style="background-color: #F89A1E;" class="px-5 py-2.5  hover:opacity-90 text-white rounded-lg text-sm font-semibold">
                <i class="fas fa-save mr-1"></i> Save Customer
            </button>
            <a href="{{ route('customer-care.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Clients
            </a>
        </div>
    </form>
</div>
<script>
function toggleBranchPicker() {
    document.getElementById('branchPicker').classList.toggle('hidden');
}

// Name the chosen branch on the button; the dropdown select is the value that
// is posted. Nothing is pre-selected unless the form is redisplayed after a
// validation error.
function showAssignedBranch() {
    var select = document.getElementById('branch_id');
    var label = document.getElementById('assignBranchLabel');
    var chosen = select.options[select.selectedIndex];
    label.textContent = select.value && chosen ? 'Assigned to: ' + chosen.text : 'Assign to';
}

(function () {
    var select = document.getElementById('branch_id');
    if (select && select.value) {
        document.getElementById('branchPicker').classList.remove('hidden');
        showAssignedBranch();
    }
})();
</script>
@endsection