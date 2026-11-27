{{-- Edit / Delete for a completed sale recorded today. Params: $sale,
     $editRoute, $destroyRoute. The flag is set by the controller using the
     same rules as the correction routes, so older sales never show the buttons. --}}
@if($sale->can_correct ?? false)
    <div class="inline-flex items-center justify-center gap-2">
        <a href="{{ route($editRoute, $sale->id) }}" title="Edit sale" aria-label="Edit sale"
           class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route($destroyRoute, $sale->id) }}" class="inline"
              data-confirm="Delete sale {{ $sale->sale_number }}? The stock will be restored and the branch admin and super admin will be notified.">
            @csrf
            @method('DELETE')
            <button type="submit" title="Delete sale" aria-label="Delete sale"
                    class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
@endif
