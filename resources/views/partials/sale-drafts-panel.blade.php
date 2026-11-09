{{-- Unfinished sales parked from a New Sale form, shared by every sales list.
     Params: $drafts (collection/array), $draftCreateRoute (route name), $accent

     Drafts sit outside the sales list and the revenue figure above until they
     are completed, so nothing here affects takings or stock. --}}
@php($accent = $accent ?? 'amber')
@if(!empty($drafts) && count($drafts) > 0)
    <div class="bg-white rounded-xl shadow p-4 mb-6 border border-{{ $accent }}-200">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-700"><i class="fas fa-file-pen mr-1 text-{{ $accent }}-600"></i> Draft Sales</h2>
                <p class="text-[10px] text-gray-400">Saved sales waiting to be finished. Nothing entered is lost.</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $accent }}-100 text-{{ $accent }}-700">{{ count($drafts) }} {{ \Illuminate\Support\Str::plural('draft', count($drafts)) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="text-left py-2 px-3">Customer</th>
                    <th class="text-center px-3">Items</th>
                    <th class="text-right px-3">Total</th>
                    <th class="text-left px-3">Last Saved</th>
                    <th class="text-center px-3">Action</th>
                </tr></thead>
                <tbody>
                    @foreach($drafts as $draft)
                        <tr class="border-t hover:bg-{{ $accent }}-50/40">
                            <td class="py-2 px-3 font-medium text-gray-700">{{ $draft['customer_label'] ?? 'Walk-in customer' }}</td>
                            <td class="px-3 text-center text-gray-600">{{ (int) ($draft['item_count'] ?? 0) }}</td>
                            <td class="px-3 text-right font-medium text-{{ $accent }}-700">TZS {{ number_format((float) ($draft['total'] ?? 0)) }}</td>
                            <td class="px-3 text-gray-500">
                                {{ !empty($draft['updated_at']) ? \Carbon\Carbon::parse($draft['updated_at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') : '—' }}
                            </td>
                            <td class="px-3 text-center">
                                <a href="{{ route($draftCreateRoute, ['draft' => $draft['id']]) }}"
                                   class="inline-flex items-center gap-1 text-{{ $accent }}-700 hover:text-{{ $accent }}-900 text-xs font-semibold">
                                    <i class="fas fa-play"></i> Continue
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
