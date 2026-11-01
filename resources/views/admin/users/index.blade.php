@extends('layouts.admin')

@section('title', __('management.user_management').' (UC-39)')
@section('page_title', __('management.users_roles'))

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <header class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-people fg-primary me-2"></i>{{ __('management.user_management') }}</h1>
            <p class="fg-3 small mb-0">{{ __('management.user_copy.intro') }}</p>
        </header>

        {{-- Filter Tabs and Search (UC-39) --}}
        <x-admin.user-filter-tabs :counts="$counts" :role="$role" :keyword="$keyword" />

        {{-- User Table Component (UC-39) --}}
        <x-admin.user-table :users="$users" :roles="$roles" />
    </div>
</div>
@endsection
