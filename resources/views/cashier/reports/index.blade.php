@extends('layouts.app')
@section('title', 'Reports')
@section('header', 'Reports')

@section('content')
@include('cashier.partials.cross-branch-banner')

@include('partials.profit-report-content', [
    'action' => route('cashier.reports.index'),
    'showBranch' => false,
])
@endsection
