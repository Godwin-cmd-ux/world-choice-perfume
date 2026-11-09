@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('cashier.sales.store'),
    'draftRoute' => route('cashier.sales.draft'),
    'accent' => 'amber',
])
@endsection
