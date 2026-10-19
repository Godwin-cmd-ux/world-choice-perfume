@extends('layouts.public')

@section('title', 'News — World Choice Perfume')

@section('content')
<section class="py-20 bg-dark-950 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">Stay Updated</span>
            <h1 class="font-display text-4xl sm:text-5xl font-bold mt-3 mb-4">
                <span class="text-white">Latest </span><span class="gold-text">News</span>
            </h1>
            <p class="text-gray-400 max-w-2xl mx-auto">Stay up to date with our latest announcements, offers, and updates from all branches.</p>
        </div>

        <div class="space-y-8">
            @forelse($posts as $post)
                {{-- Every card links to the post's own page — the list shows an
                     excerpt, the detail page reads whole. --}}
                <a href="{{ route('customer.news.show', $post->id) }}"
                   class="block bg-dark-800 border border-dark-600 rounded-2xl overflow-hidden hover:border-gold-500/40 hover:bg-dark-700/70 transition-all duration-300 group">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-64 object-cover">
                    @endif
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-3">
                            @if($post->branch)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gold-500/10 text-gold-400 border border-gold-500/20">
                                    <i class="fas fa-store mr-1"></i>{{ $post->branch->name }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-500">{{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '' }}</span>
                        </div>
                        <h2 class="font-display text-xl font-bold text-white mb-3 group-hover:text-gold-400 transition-colors">{{ $post->title }}</h2>
                        <p class="text-gray-400 leading-relaxed">{{ \Illuminate\Support\Str::limit($post->content, 260) }}</p>
                        <span class="inline-flex items-center gap-2 mt-4 text-sm font-semibold text-gold-400 transition-all">
                            Read full story <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-12">
                    <i class="fas fa-newspaper text-4xl text-gray-600 mb-4 block"></i>
                    <p class="text-gray-500 text-lg">No news posts yet. Check back soon!</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
