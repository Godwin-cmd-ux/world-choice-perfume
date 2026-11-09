@extends('stock-manager.layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('stock-manager.sales.store'),
    'draftRoute' => route('stock-manager.sales.draft'),
    'accent' => 'emerald',
    'hasDiscount' => true,
])
@endsection
