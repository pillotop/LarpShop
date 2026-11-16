@extends('layouts.app')

@section('title', __('management.vouchers.title').' | ' . $vendor->store_name)

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
    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        @if (session('success'))
            <div class="alert theme-success rounded-9 p-3 mb-4">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert theme-danger rounded-9 p-3 mb-4">
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-12 lg:col-4">
                <div class="card border-0 shadow-sm rounded-9 p-4 bg-white">
                    <h1 class="h5 fw-bold mb-1">{{ __('management.vouchers.create_title') }}</h1>
                    <p class="fg-3 small mb-3">{{ __('management.vouchers.vendor_intro', ['store' => $vendor->store_name]) }}</p>
                    <form action="{{ route('vendor.vouchers.store') }}" method="POST" class="d-flex flex-column gap-3">
                        @csrf
                        <div>
                            <label class="form-label small fw-semibold" for="vendor-voucher-code">{{ __('management.vouchers.code') }}</label>
                            <input id="vendor-voucher-code" name="code" type="text" maxlength="40" value="{{ old('code') }}" class="form-control text-uppercase" required>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold" for="vendor-voucher-title">{{ __('management.vouchers.name') }}</label>
                            <input id="vendor-voucher-title" name="title" type="text" maxlength="120" value="{{ old('title') }}" class="form-control" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-type">{{ __('management.vouchers.type') }}</label>
                                <select id="vendor-voucher-type" name="type" class="form-control">
                                    <option value="percent" @selected(old('type') === 'percent')>{{ __('management.vouchers.types.percent') }}</option>
                                    <option value="fixed" @selected(old('type') === 'fixed')>{{ __('management.vouchers.types.fixed') }}</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-value">{{ __('management.vouchers.value') }}</label>
                                <input id="vendor-voucher-value" name="value" type="number" step="0.01" min="0.01" value="{{ old('value', '10') }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-min">{{ __('management.vouchers.min_order') }}</label>
                                <input id="vendor-voucher-min" name="min_order_amount" type="number" step="0.01" min="0" value="{{ old('min_order_amount', '0') }}" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-max">{{ __('management.vouchers.max_discount') }}</label>
                                <input id="vendor-voucher-max" name="max_discount_amount" type="number" step="0.01" min="0.01" value="{{ old('max_discount_amount') }}" class="form-control">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-limit">{{ __('management.vouchers.usage_limit') }}</label>
                                <input id="vendor-voucher-limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', '100') }}" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="vendor-voucher-user-limit">{{ __('management.vouchers.per_user_limit') }}</label>
                                <input id="vendor-voucher-user-limit" name="per_user_limit" type="number" min="1" value="{{ old('per_user_limit', '1') }}" class="form-control">
                            </div>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold" for="vendor-voucher-expires">{{ __('management.vouchers.expires_at') }}</label>
                            <input id="vendor-voucher-expires" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-solid theme-primary rounded-pill fw-semibold">{{ __('management.create') }}</button>
                    </form>
                </div>
            </div>

            <div class="col-12 lg:col-8">
                <div class="card border-0 shadow-sm rounded-9 p-4 bg-white">
                    <h2 class="h5 fw-bold mb-3">{{ __('management.vouchers.title') }}</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="small fg-3">
                                    <th>{{ __('management.vouchers.code') }}</th>
                                    <th>{{ __('management.vouchers.name') }}</th>
                                    <th>{{ __('management.vouchers.value') }}</th>
                                    <th>{{ __('management.vouchers.used') }}</th>
                                    <th>{{ __('management.vouchers.status') }}</th>
                                    <th class="text-end">{{ __('management.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    <tr>
                                        <td class="fw-bold">{{ $voucher->code }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $voucher->title }}</div>
                                            @if ($voucher->expires_at)
                                                <div class="small fg-3">{{ $voucher->expires_at->format('Y-m-d H:i') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $voucher->type === 'percent' ? number_format((float) $voucher->value, 0) . '%' : '$' . number_format((float) $voucher->value, 2) }}
                                        </td>
                                        <td>{{ $voucher->used_count }}{{ $voucher->usage_limit ? ' / ' . $voucher->usage_limit : '' }}</td>
                                        <td>
                                            <span class="badge {{ $voucher->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $voucher->is_active ? __('management.vouchers.active') : __('management.vouchers.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <form action="{{ route('vendor.vouchers.toggle', $voucher) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline theme-primary">{{ __('management.vouchers.toggle') }}</button>
                                                </form>
                                                <form action="{{ route('vendor.vouchers.destroy', $voucher) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline theme-danger">{{ __('management.vouchers.delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center fg-3 py-4">{{ __('management.vouchers.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $vouchers->links('shared.pagination') }}</div>
                </div>
            </div>
        </div>
    </main>

    <x-company-footer />
    <x-side-panel />
@endsection
