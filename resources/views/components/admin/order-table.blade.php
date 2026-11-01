@props([
    'orders',
])

@if ($orders->isEmpty())
    <div class="text-center py-5 fg-3">
        <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
        {{ __('management.no_orders_match_your_filters') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
                <tr>
                    <th>{{ __('management.order') }}</th>
                    <th>{{ __('management.customer') }}</th>
                    <th class="text-center">{{ __('management.items') }}</th>
                    <th class="text-end">{{ __('management.total') }}</th>
                    <th>{{ __('management.status') }}</th>
                    <th>{{ __('management.placed') }}</th>
                    <th class="text-end">{{ __('management.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td class="fw-bold">#{{ $order->order_id }}</td>
                        <td>
                            <div class="fw-semibold fg-body">{{ $order->user?->username ?? __('management.n_a') }}</div>
                            <small class="fg-3">{{ $order->user?->email }}</small>
                        </td>
                        <td class="text-center">{{ $order->details_count }}</td>
                        <td class="text-end">{{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>
                            <span
                                class="badge rounded-pill {{ $order->status === \App\Enums\OrderStatus::Cancelled ? 'bg-danger' : ($order->status === \App\Enums\OrderStatus::Paid ? 'bg-success' : 'bg-secondary') }}"
                            >
                                {{ __('checkout.order_status.'.$order->status->value) }}
                            </span>
                        </td>
                        <td class="small fg-3">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order->order_id) }}" class="btn btn-sm btn-outline theme-primary rounded-pill px-3">{{ __('management.investigate') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small fg-3">
            {{ __('pagination.summary', ['first' => $orders->firstItem(), 'last' => $orders->lastItem(), 'total' => $orders->total()]) }}
        </div>
        <div>{{ $orders->links('shared.pagination') }}</div>
    </div>
@endif
