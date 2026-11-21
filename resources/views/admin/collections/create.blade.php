@extends('layouts.admin')

@section('title', __('promotions.create_collection'))
@section('page_title', __('promotions.create_collection'))

@section('content')
    <h1 class="h3 fw-semibold mb-4">{{ __('promotions.create_collection') }}</h1>
    @include('admin.collections.partials.form')
@endsection
