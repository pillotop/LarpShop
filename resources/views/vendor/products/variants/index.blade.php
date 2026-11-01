@extends('layouts.app')

@section('title', __('management.manage_product_variants').' (UC-37) | ' . $product->name)

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
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart ?? null"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">{{ __('management.profile') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">{{ __('management.products') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Variants ({{ $product->name }})</li>
            </ol>
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">
                    <strong>{{ __('management.success') }}</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm" role="alert">
                <div class="fw-bold mb-1">{{ __('management.please_correct_the_following_errors') }}</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 fw-bold fg-body mb-1">
                        <i class="bi bi-diagram-3-fill fg-primary me-2"></i>{{ __('management.display.product_variants') }} {{ $product->name }}
                    </h1>
                    <p class="fg-3 small mb-0">
                        {{ __('management.base_price') }} <strong class="fg-success">${{ number_format($product->price, 2) }}</strong> {{ __('management.base_stock') }} <strong>{{ $product->stock }}</strong> {{ __('management.store_label') }} <strong>{{ $vendor->store_name }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.products.index') }}" class="btn btn-outline theme-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i>{{ __('management.back_to_products') }}
                    </a>
                    <button type="button" class="btn btn-solid theme-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#createVariantForm" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('management.add_variant') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Add Variant Form (Collapsible) --}}
        <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="createVariantForm">
            <div class="card border-0 shadow-sm rounded-9 p-4 bg-1">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>{{ __('management.new_product_variant') }}</h2>
                <form action="{{ route('vendor.products.variants.store', $product) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 md:col-4">
                            <label class="form-label fw-semibold small">{{ __('management.variant_name') }} <span class="fg-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-5" placeholder="{{ __('management.e_g_size_xl_color_black') }}" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12 md:col-3">
                            <label class="form-label fw-semibold small">{{ __('management.sku') }}</label>
                            <input type="text" name="sku" class="form-control rounded-5" placeholder="{{ __('management.e_g_sku_prod_xl') }}" value="{{ old('sku') }}">
                        </div>
                        <div class="col-12 md:col-2">
                            <label class="form-label fw-semibold small">{{ __('management.price_text') }} <span class="fg-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control rounded-5" value="{{ old('price', $product->price) }}" required>
                        </div>
                        <div class="col-12 md:col-2">
                            <label class="form-label fw-semibold small">{{ __('management.stock') }} <span class="fg-danger">*</span></label>
                            <input type="number" min="0" name="stock" class="form-control rounded-5" value="{{ old('stock', 0) }}" required>
                        </div>
                        <div class="col-12 md:col-1 d-flex flex-column justify-content-end">
                            <div class="d-flex align-items-center gap-2 d-flex align-items-center gap-2 mb-2">
                                <input type="hidden" name="is_active" value="0">
                                <input class="switch" type="checkbox" role="switch" name="is_active" value="1" id="isActiveSwitch" checked>
                                <label class="form-label small" for="isActiveSwitch">{{ __('management.active') }}</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-outline theme-secondary rounded-pill px-3 me-2" data-bs-toggle="collapse" data-bs-target="#createVariantForm">{{ __('management.cancel') }}</button>
                            <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4">{{ __('management.save_variant') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Variants Table --}}
        <div class="card border-0 shadow-sm rounded-9 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-1">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>{{ __('management.variant_name') }}</th>
                            <th>{{ __('management.sku') }}</th>
                            <th>{{ __('management.price') }}</th>
                            <th>{{ __('management.stock') }}</th>
                            <th>{{ __('management.status') }}</th>
                            <th class="text-end pe-4">{{ __('management.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $variant)
                            <tr>
                                <td class="ps-4 fg-3 small">{{ $variant->variant_id }}</td>
                                <td class="fw-semibold">{{ $variant->name }}</td>
                                <td><span class="badge bg-1 fg-body border">{{ $variant->sku ?? __('management.n_a') }}</span></td>
                                <td class="fg-success fw-bold">${{ number_format($variant->price, 2) }}</td>
                                <td>
                                    @if ($variant->stock <= 0)
                                        <span class="badge bg-danger">{{ __('management.out_of_stock') }}</span>
                                    @elseif ($variant->stock <= 5)
                                        <span class="badge bg-warning fg-body">{{ __('management.low_stock_count', ['count' => $variant->stock]) }}</span>
                                    @else
                                        <span class="badge bg-subtle-success fg-success border border-success">{{ $variant->stock }} {{ __('management.quantity_labels.in_stock') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($variant->is_active)
                                        <span class="badge bg-subtle-success fg-success">{{ __('management.active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('management.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline theme-primary rounded-pill px-2 me-1" data-bs-toggle="dialog" data-bs-target="#editModal{{ $variant->variant_id }}">
                                        <i class="bi bi-pencil me-1"></i>{{ __('management.edit') }}
                                    </button>
                                    <form action="{{ route('vendor.products.variants.destroy', [$product, $variant]) }}" method="POST" class="d-inline" data-confirm="{{ __('management.are_you_sure_you_want_to_delete_this_variant') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Edit Variant Modal --}}
                            <dialog class="dialog" id="editModal{{ $variant->variant_id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $variant->variant_id }}">
                                        <form action="{{ route('vendor.products.variants.update', [$product, $variant]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="dialog-header">
                                                <h5 class="dialog-title fw-bold" id="editModalLabel{{ $variant->variant_id }}">{{ __('management.edit_variant') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="{{ __('management.close') }}"></button>
                                            </div>
                                            <div class="dialog-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">{{ __('management.variant_name') }} <span class="fg-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control rounded-5" value="{{ old('name', $variant->name) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">{{ __('management.sku') }}</label>
                                                    <input type="text" name="sku" class="form-control rounded-5" value="{{ old('sku', $variant->sku) }}">
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">{{ __('management.price_text') }} <span class="fg-danger">*</span></label>
                                                        <input type="number" step="0.01" min="0" name="price" class="form-control rounded-5" value="{{ old('price', $variant->price) }}" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">{{ __('management.stock') }} <span class="fg-danger">*</span></label>
                                                        <input type="number" min="0" name="stock" class="form-control rounded-5" value="{{ old('stock', $variant->stock) }}" required>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 d-flex align-items-center gap-2">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input class="switch" type="checkbox" role="switch" name="is_active" value="1" id="editActiveSwitch{{ $variant->variant_id }}" @checked($variant->is_active)>
                                                    <label class="form-label small" for="editActiveSwitch{{ $variant->variant_id }}">{{ __('management.active_visible') }}</label>
                                                </div>
                                            </div>
                                            <div class="dialog-footer">
                                                <button type="button" class="btn btn-outline theme-secondary rounded-pill" data-bs-dismiss="dialog">{{ __('management.cancel') }}</button>
                                                <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4">{{ __('management.update') }}</button>
                                            </div>
                                        </form>
                                    </dialog>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="fg-3 mb-2"><i class="bi bi-diagram-3 fs-3xl"></i></div>
                                    <h5 class="fw-bold fg-body">{{ __('management.no_variants_added_yet') }}</h5>
                                    <p class="fg-3 small mb-3">{{ __('management.variants_help') }}</p>
                                    <button type="button" class="btn btn-sm btn-solid theme-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#createVariantForm">
                                        <i class="bi bi-plus-lg me-1"></i>{{ __('management.add_first_variant') }}
                                    </button>
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
