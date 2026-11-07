@extends('layouts.app')

@section('title', __('management.display.order').'' . $order->order_id . ' '.__('management.fulfillment').' (UC-34, UC-35) | ' . $vendor->store_name)

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
    @php
        $orderStatusValue = $order->status instanceof \App\Enums\OrderStatus ? $order->status->value : (string) $order->status;
        $isPaid = $orderStatusValue === 'paid';
    @endphp

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
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.orders.index') }}">{{ __('management.store_orders') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.display.order') }}{{ $order->order_id }}</li>
            </ol>
        </nav>

        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- Left Column: Order Summary & Shipping Destination --}}
            <div class="lg:col-4">
                <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
                        <div>
                            <div class="fg-3 small fw-semibold">{{ __('management.order_reference') }}</div>
                            <h4 class="fw-bold fg-body mb-0">#{{ $order->order_id }}</h4>
                        </div>
                        <span @class([
                            'badge rounded-pill px-3 py-2',
                            'bg-subtle-success fg-success' => $orderStatusValue === 'paid',
                            'bg-subtle-warning fg-emphasis-warning' => $orderStatusValue === 'pending',
                            'bg-subtle-secondary fg-secondary' => in_array($orderStatusValue, ['cancelled', 'expired'], true),
                        ])>
                            {{ __('checkout.order_status.'.$orderStatusValue) }}
                        </span>
                    </div>

                    <dl class="row mb-0 small">
                        <dt class="col-5 fg-3">{{ __('management.placed_at') }}</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->created_at?->format('Y-m-d H:i') }}</dd>

                        <dt class="col-5 fg-3">{{ __('management.customer') }}</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->user?->username ?? __('management.guest') }}</dd>

                        <dt class="col-5 fg-3">{{ __('management.email') }}</dt>
                        <dd class="col-7 text-end text-break">{{ $order->user?->email ?? __('management.n_a') }}</dd>

                        <dt class="col-5 fg-3">{{ __('management.your_store_items') }}</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->details->sum('quantity') }} {{ __('management.quantity_labels.unit_s_') }}</dd>

                        <dt class="col-5 fg-3 pt-2 border-top">{{ __('management.store_subtotal') }}</dt>
                        <dd class="col-7 fw-bold fg-success fs-lg text-end pt-2 border-top mb-0">${{ $vendorSubtotal }}</dd>
                    </dl>
                </div>

                <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
                    <h5 class="fw-bold fg-body pb-2 border-bottom mb-3">
                        <i class="bi bi-geo-alt fg-primary me-1"></i>{{ __('management.shipping_address') }}
                    </h5>
                    @if ($shippingAddress)
                        <div class="fw-bold fg-body">{{ $shippingAddress->recipient_name }}</div>
                        <div class="fg-3 small mb-2">{{ $shippingAddress->phone_number }}</div>
                        <div class="small fg-body">
                            <div>{{ $shippingAddress->address_line_1 }}</div>
                            @if ($shippingAddress->address_line_2)
                                <div>{{ $shippingAddress->address_line_2 }}</div>
                            @endif
                            <div>
                                {{ $shippingAddress->city }}{{ $shippingAddress->region ? ', ' . $shippingAddress->region : '' }}
                                {{ $shippingAddress->postal_code }}
                            </div>
                            <div class="fw-semibold">{{ $shippingAddress->country_code }}</div>
                        </div>
                    @else
                        <p class="fg-3 small mb-0">{{ __('management.no_shipping_address_recorded_for_this_order') }}</p>
                    @endif
                </div>

                <a href="{{ route('vendor.orders.index') }}" class="btn btn-solid theme-secondary rounded-pill w-100">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('management.back_to_store_orders') }}
                </a>
            </div>

            {{-- Right Column: Vendor Order Items & Fulfillment Controls (UC-35) --}}
            <div class="lg:col-8">
                <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                        <div>
                            <h4 class="fw-bold fg-body mb-1">
                                <i class="bi bi-box2-heart fg-primary me-2"></i>{{ __('management.managed_by', ['name' => $vendor->store_name]) }}
                            </h4>
                            <p class="fg-3 small mb-0">
                                {{ __('management.only_order_items_belonging_to_your_store_are_shown_below') }}
                            </p>
                        </div>

                        @if ($isPaid)
                            {{-- Bulk update all vendor items in this order --}}
                            <form action="{{ route('vendor.orders.fulfillment.update', $order->order_id) }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                                @csrf
                                @method('PATCH')
                                <select name="fulfillment_status" class="form-control form-control-sm rounded-pill" aria-label="{{ __('management.bulk_fulfillment_status') }}">
                                    @foreach ($fulfillmentStatuses as $statusCase)
                                        <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-solid theme-primary rounded-pill px-3 text-nowrap fw-semibold">
                                    {{ __('management.update_all_items') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @if (! $isPaid)
                        <div class="alert theme-warning rounded-9 d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>
                                {{ __('management.fulfillment_payment_notice', ['status' => __('checkout.order_status.'.$orderStatusValue)]) }}
                            </span>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th scope="col">{{ __('management.product') }}</th>
                                    <th scope="col" class="text-center">{{ __('management.qty') }}</th>
                                    <th scope="col">{{ __('management.unit_price') }}</th>
                                    <th scope="col">{{ __('management.line_total') }}</th>
                                    <th scope="col">{{ __('management.fulfillment_status_uc_35') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->details as $detail)
                                    @php
                                        $fStatus = $detail->fulfillment_status instanceof \App\Enums\FulfillmentStatus
                                            ? $detail->fulfillment_status
                                            : \App\Enums\FulfillmentStatus::from((string) $detail->fulfillment_status);
                                        $lineTotal = number_format((float) $detail->unit_price * (int) $detail->quantity, 2);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold fg-body">{{ $detail->product?->name ?? __('management.product_number', ['id' => $detail->product_id]) }}</div>
                                            <small class="fg-3">{{ __('management.display.item_id') }}{{ $detail->order_detail_id }}</small>
                                            @if ($detail->shipped_at)
                                                <div class="fg-3 small">
                                                    <i class="bi bi-truck me-1"></i>{{ __('management.display.shipped') }} {{ $detail->shipped_at->format('Y-m-d H:i') }}
                                                </div>
                                            @endif
                                            @if ($detail->delivered_at)
                                                <div class="fg-success small">
                                                    <i class="bi bi-check2-all me-1"></i>{{ __('management.display.delivered') }} {{ $detail->delivered_at->format('Y-m-d H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">{{ $detail->quantity }}</td>
                                        <td>${{ number_format((float) $detail->unit_price, 2) }}</td>
                                        <td class="fw-bold fg-success">${{ $lineTotal }}</td>
                                        <td>
                                            @if ($isPaid)
                                                <form action="{{ route('vendor.orders.fulfillment.update', $order->order_id) }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="order_detail_id" value="{{ $detail->order_detail_id }}">
                                                    <select name="fulfillment_status" class="form-control form-control-sm rounded-pill" style="width: 145px;">
                                                        @foreach ($fulfillmentStatuses as $statusCase)
                                                            <option
                                                                value="{{ $statusCase->value }}"
                                                                @selected($fStatus === $statusCase)
                                                                @disabled(! $fStatus->canTransitionTo($statusCase))
                                                            >
                                                                {{ $statusCase->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline theme-inverse rounded-pill px-3">
                                                        {{ __('management.save') }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-subtle-secondary fg-secondary rounded-pill px-3 py-1">
                                                    {{ $fStatus->label() }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
