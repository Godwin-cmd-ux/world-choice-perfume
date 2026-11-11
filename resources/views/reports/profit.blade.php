@extends('layouts.app')
@section('title', 'Profit Report')
@section('header', 'Profit Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

@include('partials.profit-report-content', [
    'action' => route($routePrefix . '.profit'),
    'showBranch' => false,
])
@endsection
