{{-- Crown that marks a loyal customer next to their name.
     Params: $loyal (bool) — pass the flag for this customer. --}}
@if(!empty($loyal))
    <span class="inline-flex items-center text-amber-500 ml-1 align-middle" title="Loyal customer" aria-label="Loyal customer">
        <i class="fas fa-crown"></i>
    </span>
@endif
