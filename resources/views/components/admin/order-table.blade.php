@props([
    'orders',
])

@if ($orders->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        No orders match your filters.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-muted">
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td class="fw-bold">#{{ $order->order_id }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $order->user?->username ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $order->user?->email }}</small>
                        </td>
                        <td class="text-center">{{ $order->details_count }}</td>
                        <td class="text-end">{{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $order->status === 'disputed' ? 'bg-danger' : ($order->status === 'delivered' ? 'bg-success' : 'bg-secondary') }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Investigate</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing <strong>{{ $orders->firstItem() }}</strong> to <strong>{{ $orders->lastItem() }}</strong> of <strong>{{ $orders->total() }}</strong> orders
        </div>
        <div>{{ $orders->links('pagination::bootstrap-5') }}</div>
    </div>
@endif
