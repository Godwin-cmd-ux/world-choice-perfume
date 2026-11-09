@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('seller.sales.store'),
    'draftRoute' => route('seller.sales.draft'),
    'accent' => 'cyan',
])
@endsection
