@extends('layouts.app')
@section('title', 'Reports')
@section('header', 'Reports')

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

@if(!empty($branchName))
    <p class="text-sm text-gray-500 mb-5">
        <i class="fas fa-store mr-1 text-amber-600"></i>
        Showing reports for <strong class="text-gray-700">{{ $branchName }}</strong>
    </p>
@endif

@include('reports.partials.cards', ['routePrefix' => $routePrefix])
@endsection
