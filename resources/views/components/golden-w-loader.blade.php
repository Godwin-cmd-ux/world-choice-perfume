{{--
    World Choice Perfume — Golden Signature W loader.

    The brand mark (public/images/golden-w-mark.png, keyed off its black plate by
    scripts/make-loader-mark.py) with a band of champagne light travelling across
    it. Purely decorative layers are aria-hidden; the component announces itself
    once through role="status", so the repeated sweep is never re-announced.

    Usage:
        <x-golden-w-loader />                                  compact, mark only
        <x-golden-w-loader variant="inline" />                 inside a button/badge
        <x-golden-w-loader variant="page" />                   full-page: mark + brand
        <x-golden-w-loader variant="page" :label="false" />    full-page: mark only
        <x-golden-w-loader message="Getting your location…" /> custom announcement

    Background-free by design: this paints only the mark and its sweep, so the
    page's own background (dark public pages, light staff pages) shows through.
    Styles live in partials/golden-w-loader-styles.blade.php, included by both
    layouts' <head> — the project loads no Vite bundle, so global CSS belongs
    there (same as partials/theme.blade.php).
--}}
@props(['variant' => 'compact', 'label' => null, 'message' => null])

@php
    $variant = in_array($variant, ['inline', 'compact', 'page'], true) ? $variant : 'compact';
    // Brand wordmark: off for inline, on by default for the full-page variant,
    // always overridable with :label="false" or a custom string.
    $showBrand = $label === false ? false : ($label === null ? $variant === 'page' : true);
    $brandText = is_string($label) ? $label : 'World Choice Perfume';
@endphp

<span class="gw-loader gw-loader--{{ $variant }}" role="status" aria-label="{{ $message ?? 'Loading' }}">
    <span class="gw-loader__mark" aria-hidden="true">
        <img class="gw-loader__base" src="{{ asset('images/golden-w-mark.png') }}" alt=""
             width="512" height="512" decoding="async">
        <img class="gw-loader__glow" src="{{ asset('images/golden-w-mark.png') }}" alt=""
             width="512" height="512" decoding="async">
    </span>
    @if ($showBrand)
        <span class="gw-loader__brand" aria-hidden="true">{{ $brandText }}</span>
    @endif
</span>
