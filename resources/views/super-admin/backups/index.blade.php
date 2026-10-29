@extends('layouts.app')
@section('title', 'Backups')
@section('header', 'Backups')
@section('subtitle', 'Every nightly snapshot of the database, filed in Cloudinary by date')

@section('header-actions')
    <form method="POST"
          action="{{ route('super-admin.backups.induce') }}"
          onsubmit="return confirm('Take a backup of the database right now?\n\nEvery table is dumped and filed in Cloudinary under today&#39;s date. If a backup has already been taken today, today&#39;s files and record are replaced.')">
        @csrf
        <button type="submit"
                style="background-color: #F89A1E;"
                class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-bolt mr-1"></i> Induce Backup
        </button>
    </form>
    <a href="{{ route('super-admin.backups.index') }}"
       class="border border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-sync-alt mr-1"></i> Refresh
    </a>
@endsection

@section('content')
@php
    $human = function (int $bytes): string {
        if ($bytes <= 0) return '—';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024) . ' KB';
        return $bytes . ' B';
    };
@endphp

@if($cloudinaryWarning)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm mb-6">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Cloudinary could not be read:</strong> {{ $cloudinaryWarning }}
        This list shows only what the database remembers, so a backup taken but not recorded may be missing here.
    </div>
@endif

@if($imported > 0)
    <div class="bg-sky-50 border border-sky-200 text-sky-800 rounded-xl px-4 py-3 text-sm mb-6">
        <i class="fas fa-cloud-download-alt mr-1"></i>
        {{ $imported }} {{ \Illuminate\Support\Str::plural('backup', $imported) }} found in Cloudinary that the database did not list yet — added below.
    </div>
@endif

{{-- Summary --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-2xl font-bold text-gray-800">{{ count($days) }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Backups stored</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-2xl font-bold text-emerald-700">{{ $latest ? \Carbon\Carbon::parse($latest)->format('d M Y') : '—' }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Latest backup</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-2xl font-bold text-slate-600">{{ $human($totalBytes) }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Stored in Cloudinary</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between flex-wrap gap-2">
        <span class="text-sm font-semibold text-gray-700">
            <i class="fas fa-database text-gray-400 mr-1"></i> Backups by date
        </span>
        <span class="text-xs text-gray-500">
            Cloudinary folder <code class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $cloudinaryFolder }}/backup_&lt;date&gt;/</code>
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Date</th>
                    <th class="text-left px-4">Schema</th>
                    <th class="text-left px-4">Seed (data)</th>
                    <th class="text-right px-4">Rows</th>
                    <th class="text-right px-4">Took</th>
                    <th class="text-right px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($days as $day)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($day->date)->format('D, d M Y') }}</p>
                            <span class="text-[11px] text-gray-400">
                                @if($day->created_at)
                                    filed {{ \Carbon\Carbon::parse($day->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('H:i') }} EAT
                                @else
                                    found in Cloudinary
                                @endif
                            </span>
                            @if($day->status === 'imported')
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800">imported</span>
                            @endif
                        </td>
                        <td class="px-4">
                            @if($day->schema_bytes > 0)
                                <span class="text-gray-700">{{ $human($day->schema_bytes) }}</span>
                                <a href="{{ route('super-admin.backups.download', [$day->date, 'schema']) }}"
                                   class="ml-2 text-sky-700 hover:underline text-xs">
                                    <i class="fas fa-download"></i> download
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4">
                            @if($day->seed_bytes > 0)
                                <span class="text-gray-700">{{ $human($day->seed_bytes) }}</span>
                                <a href="{{ route('super-admin.backups.download', [$day->date, 'seed']) }}"
                                   class="ml-2 text-sky-700 hover:underline text-xs">
                                    <i class="fas fa-download"></i> download
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 text-right text-gray-700">
                            {{ $day->rows_count > 0 ? number_format($day->rows_count) : '—' }}
                            @if($day->tables_count > 0)
                                <span class="block text-[11px] text-gray-400">{{ $day->tables_count }} tables</span>
                            @endif
                        </td>
                        <td class="px-4 text-right text-gray-600">
                            {{ $day->seconds !== null ? number_format($day->seconds, 1) . 's' : '—' }}
                        </td>
                        <td class="px-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('super-admin.backups.download', [$day->date, 'seed']) }}"
                                   title="Download the data for {{ $day->date }}"
                                   class="text-sky-600 hover:text-sky-800 px-2 py-1">
                                    <i class="fas fa-file-arrow-down"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('super-admin.backups.destroy', $day->date) }}"
                                      onsubmit="return confirm('Delete the backup for {{ $day->date }} permanently?\n\nBoth SQL files are removed from Cloudinary and the record is removed from the database. This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete the backup for {{ $day->date }}"
                                            class="text-red-600 hover:text-red-800 px-2 py-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-database text-3xl mb-2 block"></i>
                            No backups yet.
                            <span class="block text-xs mt-1">
                                The snapshot is taken every midnight ({{ $scheduleTimezone }}), and any backup already in
                                Cloudinary appears here on refresh.
                            </span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(count($days) > 0)
        <div class="px-4 py-3 border-t bg-gray-50 text-xs text-gray-500">
            To restore a day: run its <code class="bg-white px-1 rounded">schema_{{ $latest }}.sql</code> first, then
            <code class="bg-white px-1 rounded">seed_{{ $latest }}.sql</code>.
        </div>
    @endif
</div>
@endsection
