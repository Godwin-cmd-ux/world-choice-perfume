{{-- Draft sales, shared by every sales list.

     Params: $drafts (collection/array), $draftCreateRoute, $draftDeleteRoute,
     $accent.

     The button sits beside New Sale in the page header. Opening it lists the
     parked sales; each one can be reopened on the sale form to be completed, or
     deleted. Drafts are not sales: nothing here affects revenue or stock. --}}
@php
    $accent = $accent ?? 'amber';
    $draftDeleteRoute = $draftDeleteRoute ?? null;
    $draftCount = !empty($drafts) ? count($drafts) : 0;
    // Written as whole class strings so the Tailwind build keeps them.
    $accentButton = $accent === 'emerald'
        ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'
        : 'border-amber-300 text-amber-700 hover:bg-amber-50';
    $accentBadge = $accent === 'emerald'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-amber-100 text-amber-700';
    $accentIcon = $accent === 'emerald' ? 'text-emerald-600' : 'text-amber-600';
@endphp

<button type="button" onclick="document.getElementById('saleDraftsModal').classList.remove('hidden')"
    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border bg-white transition {{ $accentButton }}">
    <i class="fas fa-file-pen"></i> Drafts
    @if($draftCount > 0)
        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $accentBadge }}">{{ $draftCount }}</span>
    @endif
</button>

@push('scripts')
<div id="saleDraftsModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeSaleDrafts()"></div>
    <div class="relative w-full max-w-3xl max-h-[85vh] flex flex-col rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b">
            <div>
                <h2 class="font-semibold text-gray-800"><i class="fas fa-file-pen mr-1 {{ $accentIcon }}"></i> Draft Sales</h2>
                <p class="text-xs text-gray-400 mt-1">Saved sales waiting to be finished. Open one to complete it, or delete it.</p>
            </div>
            <button type="button" onclick="closeSaleDrafts()" class="text-gray-400 hover:text-gray-600" aria-label="Close drafts">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-4">
            @if($draftCount === 0)
                <div class="text-center py-10">
                    <i class="fas fa-file-pen text-3xl text-gray-200"></i>
                    <p class="mt-3 text-sm text-gray-500">No saved drafts yet.</p>
                    <p class="text-xs text-gray-400">Use Save as Draft on a New Sale to keep it for later.</p>
                </div>
            @else
                <ul class="divide-y">
                    @foreach($drafts as $draft)
                        <li class="py-4 flex flex-wrap items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-800 truncate">{{ $draft['customer_label'] ?? 'Walk-in customer' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ (int) ($draft['item_count'] ?? 0) }} {{ \Illuminate\Support\Str::plural('item', (int) ($draft['item_count'] ?? 0)) }}
                                    · TZS {{ number_format((float) ($draft['total'] ?? 0)) }}
                                    · Saved {{ !empty($draft['updated_at']) ? \Carbon\Carbon::parse($draft['updated_at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') : '—' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route($draftCreateRoute, ['draft' => $draft['id']]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white"
                                   style="background-color: #F89A1E;">
                                    <i class="fas fa-play"></i> Continue
                                </a>
                                @if($draftDeleteRoute)
                                    <form method="POST" action="{{ route($draftDeleteRoute, $draft['id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-confirm="Delete this draft sale? This cannot be undone."
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border border-red-200 text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash-can"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<script>
function closeSaleDrafts() {
    var modal = document.getElementById('saleDraftsModal');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSaleDrafts();
});
</script>
@endpush
