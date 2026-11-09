{{-- Save as Draft + Complete Sale, the same pair on every New Sale form.
     Params: $draftRoute (the role's POST draft route), $accent (amber|emerald)

     Saving a draft skips the browser's required-field checks on purpose: a
     draft may be parked with only a customer or half a cart filled in. --}}
<div class="flex items-center gap-2 ml-auto">
    <button type="submit" formaction="{{ $draftRoute }}" formnovalidate
        class="px-4 py-2.5 rounded-lg border border-{{ $accent }}-300 text-{{ $accent }}-700 hover:bg-{{ $accent }}-50 text-sm font-semibold transition">
        <i class="fas fa-floppy-disk mr-1"></i> Save as Draft
    </button>
    <button type="submit" style="background-color: #F89A1E;"
        class="px-6 py-2.5 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition" id="submitBtn">
        <i class="fas fa-check-circle mr-1"></i> Complete Sale
    </button>
</div>
