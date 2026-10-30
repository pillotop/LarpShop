@extends('layouts.app')

@section('title', 'Platform Categories | ' . $vendor->store_name)

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">Products & Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Categories</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" />

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-tags text-primary me-2"></i>Platform Product Categories
                    </h3>
                    <p class="text-muted small mb-0">
                        Browse shared catalog categories or propose a new category. Shared categories can only be modified or disabled by administrators.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.categories.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>New Category
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">#ID</th>
                            <th scope="col">Category Name</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Parent Category</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end" style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $cat)
                            <tr class="{{ ! $cat->is_active ? 'table-light opacity-75' : '' }}">
                                <td class="fw-semibold text-muted">#{{ $cat->category_id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $cat->name }}</div>
                                    @if ($cat->description)
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 260px;">
                                            {{ $cat->description }}
                                        </small>
                                    @endif
                                </td>
                                <td><code>/categories/{{ $cat->slug }}</code></td>
                                <td>
                                    @if ($cat->parent)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-diagram-2 me-1"></i>{{ $cat->parent->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-- Root --</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($cat->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i>Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (auth()->user()?->isAdmin())
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('vendor.categories.edit', $cat->category_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <form action="{{ route('vendor.categories.toggle-status', $cat->category_id) }}" method="POST" class="m-0">
                                                @csrf
                                                @method('PATCH')
                                                @if ($cat->is_active)
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="bi bi-eye-slash me-1"></i>Disable
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                        <i class="bi bi-eye me-1"></i>Enable
                                                    </button>
                                                @endif
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">Shared Category</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder2-open fs-1 d-block mb-2 text-secondary"></i>
                                    No categories available yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
