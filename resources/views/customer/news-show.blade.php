@extends('layouts.public')

@section('title', $post->title . ' — News — World Choice Perfume')

@section('content')
<section class="py-20 bg-dark-950 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('customer.news') }}"
           class="inline-flex items-center gap-2 text-gold-400/80 hover:text-gold-400 text-sm font-semibold mb-8 transition-colors">
            <i class="fas fa-arrow-left"></i> All news
        </a>

        <article class="bg-dark-800 border border-dark-600 rounded-2xl overflow-hidden">
            @if($post->image_url)
                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-72 sm:h-96 object-cover">
            @endif
            <div class="p-6 sm:p-10">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if($post->branch)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gold-500/10 text-gold-400 border border-gold-500/20">
                            <i class="fas fa-store mr-1"></i>{{ $post->branch->name }}
                        </span>
                    @endif
                    <span class="text-xs text-gray-500">
                        {{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '' }}
                    </span>
                </div>

                <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-6">{{ $post->title }}</h1>

                {{-- The whole post — the list only ever shows an excerpt. Newlines
                     in the stored content become paragraphs, so an announcement
                     keeps its shape instead of running together. --}}
                <div class="text-gray-400 leading-relaxed text-lg space-y-4">
                    @foreach(preg_split('/\R+/', trim((string) $post->content)) as $paragraph)
                        @if($paragraph !== '')
                            <p>{{ $paragraph }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
        </article>

        <div class="flex justify-center mt-8">
            <a href="{{ route('customer.news') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 border border-dark-600 hover:border-gold-500/40 text-gold-400 rounded-lg text-sm font-medium transition">
                <i class="fas fa-newspaper"></i> Back to all news
            </a>
        </div>
    </div>
</section>
@endsection
