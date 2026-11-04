@extends('layouts.app')
@section('title', 'Shop Video')
@section('header', 'Shop Video')
@section('subtitle', 'The background clip on the public shop page')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h3 class="font-semibold text-gray-800">Current clip</h3>
                <p class="text-xs text-gray-500 mt-1">
                    @if($usingBundledClip)
                        The bundled default clip is playing. Upload a video to replace it.
                    @elseif($hasVideo)
                        Your uploaded clip is playing on the shop page.
                    @else
                        No clip is set — the shop header shows the plain dark background.
                    @endif
                </p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs whitespace-nowrap {{ $hasVideo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                {{ $hasVideo ? 'Video on' : 'No video' }}
            </span>
        </div>

        @if($previewUrl)
            <video controls muted loop playsinline preload="metadata"
                   class="w-full rounded-lg border border-gray-200 bg-black max-h-[360px]">
                <source src="{{ $previewUrl }}" type="{{ $previewType }}">
            </video>
            <p class="text-[11px] text-gray-400 mt-2 break-all">{{ $previewUrl }}</p>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-10 text-center">
                <i class="fas fa-film text-3xl text-gray-300 mb-2 block"></i>
                <p class="text-sm text-gray-500">Nothing plays on the shop header right now.</p>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-800">{{ $hasVideo ? 'Replace the clip' : 'Add a clip' }}</h3>
        <p class="text-xs text-gray-500 mt-1 mb-4">
            MP4 or WebM, 48 MB or smaller. The header plays it muted and on a loop, so export a
            compressed web version rather than the master file.
        </p>

        <form method="POST" action="{{ route('graphic-designer.shop-video.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="video" accept="video/mp4,video/webm" required
                   class="w-full px-3 py-2 border rounded-lg text-sm">
            <div class="flex flex-wrap gap-3 mt-4">
                <button type="submit" style="background-color: #F89A1E;"
                        class="hover:opacity-90 text-white px-5 py-2 rounded-lg font-medium text-sm">
                    <i class="fas fa-upload mr-1"></i> {{ $hasVideo ? 'Replace video' : 'Upload video' }}
                </button>
            </div>
        </form>

        @if($hasVideo)
            <form method="POST" action="{{ route('graphic-designer.shop-video.destroy') }}"
                  class="mt-4 pt-4 border-t border-gray-200"
                  data-confirm="Remove the clip from the shop page?">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                    <i class="fas fa-trash mr-1"></i> Remove video
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
