@extends('layouts.app')
@section('title', 'Reports')
@section('header', 'Reports')

@section('content')
@include('partials.profit-report-content', [
    'action' => route('branch-admin.reports.index'),
    'showBranch' => false,
])
@endsection
