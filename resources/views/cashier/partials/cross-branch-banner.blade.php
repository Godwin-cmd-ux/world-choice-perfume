@if(!empty($inCrossBranch))
    <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-lg text-sm mb-6 flex flex-wrap items-center justify-between gap-3">
        <span>
            <i class="fas fa-code-branch mr-2"></i>
            Shared branch view &bull; <strong>{{ $activeBranchName ?? 'branch' }}</strong> &mdash; read-only. No changes can be made while monitoring.
        </span>
        <span class="flex items-center gap-2">
            @unless(request()->routeIs('cashier.cross-branch*'))
                <a href="{{ route('cashier.cross-branch') }}" class="text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50">
                    <i class="fas fa-university mr-1"></i> All branches
                </a>
            @endunless
            <a href="{{ route('cashier.cross-branch.exit') }}" style="background-color: #F89A1E;" class="text-xs font-semibold text-white hover:opacity-90 px-3 py-1.5 rounded-lg">
                <i class="fas fa-arrow-left mr-1"></i> Exit branch
            </a>
        </span>
    </div>
@endif
