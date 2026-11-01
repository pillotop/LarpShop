@extends('layouts.admin')

@section('title', 'User Management (UC-39)')
@section('page_title', 'Users & Roles')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <header class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-people fg-primary me-2"></i>User Management</h1>
            <p class="fg-3 small mb-0">Review accounts and assign the customer or administrator role. Store ownership is managed separately under Vendors.</p>
        </header>

        {{-- Filter Tabs and Search (UC-39) --}}
        <x-admin.user-filter-tabs :counts="$counts" :role="$role" :keyword="$keyword" />

        {{-- User Table Component (UC-39) --}}
        <x-admin.user-table :users="$users" :roles="$roles" />
    </div>
</div>
@endsection
