@extends('layouts.admin')

@section('title', __('management.vouchers.title'))
@section('page_title', __('management.vouchers.title'))

@section('content')
<div class="container-fluid p-0">
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
                <h2 class="h5 fw-bold mb-1">{{ __('management.vouchers.create_title') }}</h2>
                <p class="fg-3 small mb-3">{{ __('management.vouchers.admin_intro') }}</p>
                <form action="{{ route('admin.vouchers.store') }}" method="POST" class="d-flex flex-column gap-3">
                    @csrf
                    <div>
                        <label class="form-label small fw-semibold" for="admin-voucher-code">{{ __('management.vouchers.code') }}</label>
                        <input id="admin-voucher-code" name="code" type="text" maxlength="40" value="{{ old('code') }}" class="form-control text-uppercase" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold" for="admin-voucher-title">{{ __('management.vouchers.name') }}</label>
                        <input id="admin-voucher-title" name="title" type="text" maxlength="120" value="{{ old('title') }}" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-type">{{ __('management.vouchers.type') }}</label>
                            <select id="admin-voucher-type" name="type" class="form-control">
                                <option value="percent" @selected(old('type') === 'percent')>{{ __('management.vouchers.types.percent') }}</option>
                                <option value="fixed" @selected(old('type') === 'fixed')>{{ __('management.vouchers.types.fixed') }}</option>
                                <option value="free_shipping" @selected(old('type') === 'free_shipping')>{{ __('management.vouchers.types.free_shipping') }}</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-value">{{ __('management.vouchers.value') }}</label>
                            <input id="admin-voucher-value" name="value" type="number" step="0.01" min="0" value="{{ old('value', '10') }}" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-min">{{ __('management.vouchers.min_order') }}</label>
                            <input id="admin-voucher-min" name="min_order_amount" type="number" step="0.01" min="0" value="{{ old('min_order_amount', '0') }}" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-max">{{ __('management.vouchers.max_discount') }}</label>
                            <input id="admin-voucher-max" name="max_discount_amount" type="number" step="0.01" min="0.01" value="{{ old('max_discount_amount') }}" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-limit">{{ __('management.vouchers.usage_limit') }}</label>
                            <input id="admin-voucher-limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', '500') }}" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="admin-voucher-user-limit">{{ __('management.vouchers.per_user_limit') }}</label>
                            <input id="admin-voucher-user-limit" name="per_user_limit" type="number" min="1" value="{{ old('per_user_limit', '1') }}" class="form-control">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold" for="admin-voucher-expires">{{ __('management.vouchers.expires_at') }}</label>
                        <input id="admin-voucher-expires" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}" class="form-control">
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
                                <th>{{ __('management.vouchers.scope') }}</th>
                                <th>{{ __('management.vouchers.value') }}</th>
                                <th>{{ __('management.vouchers.used') }}</th>
                                <th>{{ __('management.vouchers.status') }}</th>
                                <th class="text-end">{{ __('management.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vouchers as $voucher)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $voucher->code }}</div>
                                        <div class="small fg-3">{{ $voucher->title }}</div>
                                    </td>
                                    <td>{{ $voucher->vendor?->store_name ?? __('management.vouchers.platform_wide') }}</td>
                                    <td>
                                        @if ($voucher->type === 'percent')
                                            {{ number_format((float) $voucher->value, 0) }}%
                                        @elseif ($voucher->type === 'free_shipping')
                                            {{ __('management.vouchers.types.free_shipping') }}
                                        @else
                                            ${{ number_format((float) $voucher->value, 2) }}
                                        @endif
                                    </td>
                                    <td>{{ $voucher->used_count }}{{ $voucher->usage_limit ? ' / ' . $voucher->usage_limit : '' }}</td>
                                    <td>
                                        <span class="badge {{ $voucher->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $voucher->is_active ? __('management.vouchers.active') : __('management.vouchers.inactive') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form action="{{ route('admin.vouchers.toggle', $voucher) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline theme-primary">{{ __('management.vouchers.toggle') }}</button>
                                            </form>
                                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST">
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
</div>
@endsection
