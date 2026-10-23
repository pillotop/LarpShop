@extends('layouts.admin')

@section('title', 'Product Moderation (UC-42)')
@section('page_title', 'Product Moderation')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-box-seam text-primary me-2"></i>Product Moderation</h1>
            <p class="text-muted small mb-0">Review vendor-listed products. Only approved products are visible to customers.</p>
        </div>

        <ul class="nav nav-pills gap-2 mb-3">
            @foreach (array_merge(['all'], $statuses) as $key)
                <li class="nav-item">
                    <a href="{{ route('admin.products.moderation.index', array_filter(['status' => $key, 'q' => $keyword, 'vendor_id' => $vendorId])) }}"
                        class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === $key ? 'active' : 'bg-light text-dark' }}">
                        {{ ucfirst($key) }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="GET" action="{{ route('admin.products.moderation.index') }}" class="row g-2 mb-4">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-12 col-md-5">
                <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="Search by product name">
            </div>
            <div class="col-12 col-md-4">
                <select name="vendor_id" class="form-select">
                    <option value="">All stores</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->vendor_id }}" @selected((int) $vendorId === $vendor->vendor_id)>{{ $vendor->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filter</button>
                <a href="{{ route('admin.products.moderation.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No products match your filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Product</th>
                            <th>Store</th>
                            <th>Category</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Stock</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted">#{{ $product->product_id }}</small>
                                </td>
                                <td>{{ $product->vendor?->store_name ?? 'N/A' }}</td>
                                <td>{{ $product->category?->name ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format((float) $product->price, 2) }}</td>
                                <td class="text-center">{{ $product->stock }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $product->status === 'approved' ? 'bg-success' : ($product->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst($product->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        @if ($product->status !== 'approved')
                                            <form method="POST" action="{{ route('admin.products.moderation.approve', $product->product_id) }}" class="m-0">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                                            </form>
                                        @endif
                                        @if ($product->status !== 'rejected')
                                            <form method="POST" action="{{ route('admin.products.moderation.reject', $product->product_id) }}" class="m-0" onsubmit="return confirm('Reject this product? It will be hidden from customers.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Reject</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing <strong>{{ $products->firstItem() }}</strong> to <strong>{{ $products->lastItem() }}</strong> of <strong>{{ $products->total() }}</strong> products
                </div>
                <div>{{ $products->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
