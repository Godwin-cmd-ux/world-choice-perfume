{{-- Save as Draft, shown in the products step so a half-built sale can be
     parked before payment. Params: $draftRoute (the role's POST draft route),
     $accent (amber|emerald|cyan)

     Saving a draft skips the browser's required-field checks on purpose: a
     draft may be parked with only a customer or half a cart filled in. --}}
<button type="submit" formaction="{{ $draftRoute }}" formnovalidate data-save-draft
    class="px-4 py-2.5 rounded-lg border border-{{ $accent }}-300 text-{{ $accent }}-700 hover:bg-{{ $accent }}-50 text-sm font-semibold transition">
    <i class="fas fa-floppy-disk mr-1"></i> Save as Draft
</button>
