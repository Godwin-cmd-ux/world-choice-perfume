{{--
    The personal order name: a short private label the picking staff member
    gives an order so they can recognise the customer ("Mama Asha",
    "Customer from Mwanza").

    It is only ever an ADDITIONAL identifier. The official order number is
    never replaced by it, and no customer detail is touched.

    Expects:
      $order      the order object
      $nameRoute  route name that saves the label, e.g. 'cashier.orders.personal-name'
      $canName    true only for the staff member who owns the order (or Super Admin)

    Optional:
      $modalId           override the dialog id if the same order appears twice
      $showOrderNumber   print the official order number under the label, for
                         pages that have no dedicated order-number column
--}}
@php
    $label = trim((string) ($order->personal_order_name ?? ''));
    $modalId = $modalId ?? ('order-name-' . ($order->id ?? 'unknown'));
    $canName = $canName ?? false;
    $showOrderNumber = $showOrderNumber ?? false;
@endphp

<div class="flex items-center gap-2 flex-wrap">
    @if($label !== '')
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium">
            <i class="fas fa-tag"></i>{{ $label }}
        </span>
    @else
        <span class="text-xs text-gray-400">No personal name</span>
    @endif

    @if($canName)
        <button type="button" class="text-xs font-medium text-amber-700 hover:underline"
                onclick="document.getElementById('{{ $modalId }}').showModal()">
            <i class="fas fa-tag mr-1"></i>{{ $label !== '' ? 'Edit Name' : 'Name This Order' }}
        </button>
    @endif
</div>

@if($showOrderNumber)
    <p class="text-xs text-gray-500 mt-1">Order #{{ $order->order_number ?? 'N/A' }}</p>
@endif

@if($canName)
    <dialog id="{{ $modalId }}" class="p-0 w-full max-w-sm rounded-xl backdrop:bg-black/50">
        <form method="POST" action="{{ route($nameRoute, $order->id) }}" class="bg-white rounded-xl p-5">
            @csrf

            <h3 class="font-semibold text-gray-800">Name this order</h3>
            <p class="text-xs text-gray-500 mt-1 mb-3">
                A private reminder for you. Order #{{ $order->order_number ?? 'N/A' }} stays the official number.
            </p>

            @error('personal_order_name')
                <p class="text-xs text-red-600 mb-2">{{ $message }}</p>
            @enderror

            <input type="text"
                   name="personal_order_name"
                   value="{{ old('personal_order_name', $label) }}"
                   maxlength="120"
                   placeholder="e.g. Customer from Mwanza"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            <p class="text-[11px] text-gray-400 mt-1">Up to 120 characters. Clear the box to remove the name.</p>

            <div class="flex items-center justify-between gap-2 mt-4">
                @if($label !== '')
                    <button type="submit"
                            class="text-xs text-red-600 hover:underline"
                            onclick="this.form.querySelector('[name=personal_order_name]').value=''">Remove name</button>
                @else
                    <span></span>
                @endif

                <div class="flex items-center gap-2">
                    <button type="button" class="px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                            onclick="this.closest('dialog').close()">Cancel</button>
                    <button type="submit" style="background-color:#F89A1E"
                            class="px-4 py-2 text-sm text-white font-medium rounded-lg hover:opacity-90">Save Name</button>
                </div>
            </div>
        </form>
    </dialog>
@endif
