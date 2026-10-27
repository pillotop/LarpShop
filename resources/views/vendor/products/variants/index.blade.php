@extends('layouts.app')

@section('title', 'Manage Product Variants (UC-37) | ' . $product->name)

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
    <header class="container sticky-top" style="background-color: #ffffff99;">
        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories ?? []"
            :cart="$cart ?? null"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">Profile</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">Variants ({{ $product->name }})</li>
            </ol>
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">
                    <strong>Success!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm" role="alert">
                <div class="fw-bold mb-1">Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">
                        <i class="bi bi-diagram-3-fill text-primary me-2"></i>Product Variants: {{ $product->name }}
                    </h1>
                    <p class="text-muted small mb-0">
                        Base Price: <strong class="text-success">${{ number_format($product->price, 2) }}</strong> | 
                        Base Stock: <strong>{{ $product->stock }}</strong> | 
                        Store: <strong>{{ $vendor->store_name }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i>Back to Products
                    </a>
                    <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#createVariantForm" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i>Add Variant
                    </button>
                </div>
            </div>
        </div>

        {{-- Add Variant Form (Collapsible) --}}
        <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="createVariantForm">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>New Product Variant</h2>
                <form action="{{ route('vendor.products.variants.store', $product) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small">Variant Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Size XL / Color Black" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold small">SKU</label>
                            <input type="text" name="sku" class="form-control rounded-3" placeholder="e.g. SKU-PROD-XL" value="{{ old('sku') }}">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-semibold small">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control rounded-3" value="{{ old('price', $product->price) }}" required>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-semibold small">Stock <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stock" class="form-control rounded-3" value="{{ old('stock', 0) }}" required>
                        </div>
                        <div class="col-12 col-md-1 d-flex flex-column justify-content-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActiveSwitch" checked>
                                <label class="form-check-label small" for="isActiveSwitch">Active</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3 me-2" data-bs-toggle="collapse" data-bs-target="#createVariantForm">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Save Variant</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Variants Table --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Variant Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $variant)
                            <tr>
                                <td class="ps-4 text-muted small">{{ $variant->variant_id }}</td>
                                <td class="fw-semibold">{{ $variant->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $variant->sku ?? 'N/A' }}</span></td>
                                <td class="text-success fw-bold">${{ number_format($variant->price, 2) }}</td>
                                <td>
                                    @if ($variant->stock <= 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif ($variant->stock <= 5)
                                        <span class="badge bg-warning text-dark">{{ $variant->stock }} (Low)</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $variant->stock }} in stock</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($variant->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $variant->variant_id }}">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>
                                    <form action="{{ route('vendor.products.variants.destroy', [$product, $variant]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this variant?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Edit Variant Modal --}}
                            <div class="modal fade" id="editModal{{ $variant->variant_id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $variant->variant_id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <form action="{{ route('vendor.products.variants.update', [$product, $variant]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold" id="editModalLabel{{ $variant->variant_id }}">Edit Variant</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Variant Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $variant->name) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">SKU</label>
                                                    <input type="text" name="sku" class="form-control rounded-3" value="{{ old('sku', $variant->sku) }}">
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Price ($) <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" min="0" name="price" class="form-control rounded-3" value="{{ old('price', $variant->price) }}" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Stock <span class="text-danger">*</span></label>
                                                        <input type="number" min="0" name="stock" class="form-control rounded-3" value="{{ old('stock', $variant->stock) }}" required>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="editActiveSwitch{{ $variant->variant_id }}" @checked($variant->is_active)>
                                                    <label class="form-check-label small" for="editActiveSwitch{{ $variant->variant_id }}">Active / Visible</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary rounded-pill px-4">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="bi bi-diagram-3 fs-1"></i></div>
                                    <h5 class="fw-bold text-dark">No variants added yet</h5>
                                    <p class="text-muted small mb-3">You can add variants like sizes, colors, or specifications for this product.</p>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#createVariantForm">
                                        <i class="bi bi-plus-lg me-1"></i>Add First Variant
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection
