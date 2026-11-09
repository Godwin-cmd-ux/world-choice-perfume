{{-- Draft-sale state, shared by every New Sale form.
     Params: $draft (the resumed draft, or null)

     Inside the form: the hidden id that turns the next submit into "complete
     this draft", the restored-draft notice, and the payload the sale script
     uses to put the customer, cart, bottles and payments back in place. --}}
@if(!empty($draft))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-2">
        <i class="fas fa-file-pen mt-0.5"></i>
        <div>
            <p class="font-semibold">Draft restored</p>
            <p class="text-xs text-amber-700">
                Last saved {{ !empty($draft['updated_at']) ? \Carbon\Carbon::parse($draft['updated_at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') : 'just now' }}.
                Press <b>Complete Sale</b> once the payment is done — the sale then leaves the draft list.
            </p>
        </div>
    </div>
@endif

<input type="hidden" name="draft_id" value="{{ $draft['id'] ?? old('draft_id') }}">

@if(!empty($draft))
    <script>window.SALE_DRAFT = @json($draft['payload'] ?? []);</script>
@endif
