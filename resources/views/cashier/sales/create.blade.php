@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('cashier.sales.store'),
    'accent' => 'amber',
])
@endsection
