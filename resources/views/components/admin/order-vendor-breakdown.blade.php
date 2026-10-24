@props([
    'byVendor',
])

@forelse ($byVendor as $vendorId => $details)
    @php $vendor = $details->first()->product?->vendor; @endphp
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-shop me-2"></i>{{ $vendor?->store_name ?? 'Unknown store' }}</h2>
            @if ($vendor?->user)
                <small class="text-muted">Owner: {{ $vendor->user->username }}</small>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $detail)
                        <tr>
                            <td>{{ $detail->product?->name ?? 'Deleted product #'.$detail->product_id }}</td>
                            <td class="text-center">{{ $detail->quantity }}</td>
                            <td class="text-end">{{ number_format((float) $detail->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($detail->quantity * (float) $detail->unit_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted">
        This order has no line items.
    </div>
@endforelse
