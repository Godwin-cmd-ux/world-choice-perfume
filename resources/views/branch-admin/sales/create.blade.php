@extends('layouts.app')
@section('title', 'New Sale')
@section('header', 'New Sale')

@section('content')
@include('partials.sale-form', [
    'formAction' => route('branch-admin.sales.store'),
    'accent' => 'amber',
])
@endsection
