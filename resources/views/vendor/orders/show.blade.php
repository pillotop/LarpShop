@extends('layouts.app')

@section('title', 'Order #' . $order->order_id . ' Fulfillment (UC-34, UC-35) | ' . $vendor->store_name)

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
                <li class="breadcrumb-item"><a href="{{ route('vendor.orders.index') }}">Store Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order #{{ $order->order_id }}</li>
            </ol>
        </nav>

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

        <div class="row g-4">
            {{-- Left Column: Order Summary & Shipping Destination --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
                        <div>
                            <div class="text-muted small fw-semibold">Order Reference</div>
                            <h4 class="fw-bold text-dark mb-0">#{{ $order->order_id }}</h4>
                        </div>
                        <span @class([
                            'badge rounded-pill px-3 py-2',
                            'bg-success-subtle text-success' => $orderStatusValue === 'paid',
                            'bg-warning-subtle text-warning-emphasis' => $orderStatusValue === 'pending',
                            'bg-secondary-subtle text-secondary' => in_array($orderStatusValue, ['cancelled', 'expired'], true),
                        ])>
                            {{ ucfirst($orderStatusValue) }}
                        </span>
                    </div>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Placed At</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->created_at?->format('Y-m-d H:i') }}</dd>

                        <dt class="col-5 text-muted">Customer</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->user?->username ?? 'Guest' }}</dd>

                        <dt class="col-5 text-muted">Email</dt>
                        <dd class="col-7 text-end text-break">{{ $order->user?->email ?? 'N/A' }}</dd>

                        <dt class="col-5 text-muted">Your Store Items</dt>
                        <dd class="col-7 fw-semibold text-end">{{ $order->details->sum('quantity') }} unit(s)</dd>

                        <dt class="col-5 text-muted pt-2 border-top">Store Subtotal</dt>
                        <dd class="col-7 fw-bold text-success fs-5 text-end pt-2 border-top mb-0">${{ $vendorSubtotal }}</dd>
                    </dl>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark pb-2 border-bottom mb-3">
                        <i class="bi bi-geo-alt text-primary me-1"></i>Shipping Address
                    </h5>
                    @if ($shippingAddress)
                        <div class="fw-bold text-dark">{{ $shippingAddress->recipient_name }}</div>
                        <div class="text-muted small mb-2">{{ $shippingAddress->phone_number }}</div>
                        <div class="small text-dark">
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
                        <p class="text-muted small mb-0">No shipping address recorded for this order.</p>
                    @endif
                </div>

                <a href="{{ route('vendor.orders.index') }}" class="btn btn-light rounded-pill w-100">
                    <i class="bi bi-arrow-left me-1"></i>Back to Store Orders
                </a>
            </div>

            {{-- Right Column: Vendor Order Items & Fulfillment Controls (UC-35) --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">
                                <i class="bi bi-box2-heart text-primary me-2"></i>Items Managed by {{ $vendor->store_name }}
                            </h4>
                            <p class="text-muted small mb-0">
                                Only order items belonging to your store are shown below.
                            </p>
                        </div>

                        @if ($isPaid)
                            {{-- Bulk update all vendor items in this order --}}
                            <form action="{{ route('vendor.orders.fulfillment.update', $order->order_id) }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                                @csrf
                                @method('PATCH')
                                <select name="fulfillment_status" class="form-select form-select-sm rounded-pill" aria-label="Bulk fulfillment status">
                                    @foreach ($fulfillmentStatuses as $statusCase)
                                        <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap fw-semibold">
                                    Update All Items
                                </button>
                            </form>
                        @endif
                    </div>

                    @if (! $isPaid)
                        <div class="alert alert-warning rounded-4 d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>
                                This order is currently <strong>{{ ucfirst($orderStatusValue) }}</strong>. Fulfillment and shipping status can only be updated once payment is confirmed (<strong>Paid</strong>).
                            </span>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col" class="text-center">Qty</th>
                                    <th scope="col">Unit Price</th>
                                    <th scope="col">Line Total</th>
                                    <th scope="col">Fulfillment Status (UC-35)</th>
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
                                            <div class="fw-bold text-dark">{{ $detail->product?->name ?? 'Product #' . $detail->product_id }}</div>
                                            <small class="text-muted">Item ID: #{{ $detail->order_detail_id }}</small>
                                            @if ($detail->shipped_at)
                                                <div class="text-muted small">
                                                    <i class="bi bi-truck me-1"></i>Shipped: {{ $detail->shipped_at->format('Y-m-d H:i') }}
                                                </div>
                                            @endif
                                            @if ($detail->delivered_at)
                                                <div class="text-success small">
                                                    <i class="bi bi-check2-all me-1"></i>Delivered: {{ $detail->delivered_at->format('Y-m-d H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">{{ $detail->quantity }}</td>
                                        <td>${{ number_format((float) $detail->unit_price, 2) }}</td>
                                        <td class="fw-bold text-success">${{ $lineTotal }}</td>
                                        <td>
                                            @if ($isPaid)
                                                <form action="{{ route('vendor.orders.fulfillment.update', $order->order_id) }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="order_detail_id" value="{{ $detail->order_detail_id }}">
                                                    <select name="fulfillment_status" class="form-select form-select-sm rounded-pill" style="width: 145px;">
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
                                                    <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                                        Save
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
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
