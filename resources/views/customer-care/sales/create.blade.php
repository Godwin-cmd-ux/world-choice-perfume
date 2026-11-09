@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('customer-care.sales.store'),
    'draftRoute' => route('customer-care.sales.draft'),
    'accent' => 'amber',
    'backRoute' => route('customer-care.sales.index'),
])
@endsection
