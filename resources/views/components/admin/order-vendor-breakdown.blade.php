@props([
    'byVendor',
])

@forelse ($byVendor as $vendorId => $details)
    @php $vendor = $details->first()->product?->vendor; @endphp
    <div class="card border-0 shadow-sm rounded-9 p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-shop me-2"></i>{{ $vendor?->store_name ?? __('management.unknown_store') }}</h2>
            @if ($vendor?->user)
                <small class="fg-3">{{ __('management.display.owner') }} {{ $vendor->user->username }}</small>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-1 small text-uppercase fg-3">
                    <tr>
                        <th>{{ __('management.product') }}</th>
                        <th class="text-center">{{ __('management.qty') }}</th>
                        <th class="text-end">{{ __('management.unit_price') }}</th>
                        <th class="text-end">{{ __('management.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $detail)
                        <tr>
                            <td>{{ $detail->product?->name ?? __('management.deleted_product_number', ['id' => $detail->product_id]) }}</td>
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
    <div class="card border-0 shadow-sm rounded-9 p-4 text-center fg-3">
        {{ __('management.this_order_has_no_line_items') }}
    </div>
@endforelse
