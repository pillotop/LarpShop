@props([
    'image',
    'product',
    'isPrimary' => false,
])

<div class="col-sm-6 col-md-4 col-lg-3">
    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative {{ $isPrimary ? 'border border-2 border-primary' : '' }}">
        @if ($isPrimary)
            <div class="position-absolute top-0 start-0 m-2 z-2">
                <span class="badge bg-primary rounded-pill shadow-sm px-3 py-1">
                    <i class="bi bi-star-fill me-1"></i>Ảnh đại diện
                </span>
            </div>
        @endif

        <div class="ratio ratio-1x1 bg-light">
            <img src="{{ $image->url }}" class="card-img-top object-fit-cover" alt="{{ $image->alt_text ?? $product->name }}">
        </div>

        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div class="mb-2">
                <small class="text-muted d-block text-truncate" title="{{ $image->alt_text }}">
                    <i class="bi bi-card-text me-1"></i>{{ $image->alt_text ?: 'Không có văn bản thay thế' }}
                </small>
                <small class="text-muted">
                    <i class="bi bi-sort-numeric-down me-1"></i>Thứ tự: <strong>{{ $image->sort_order }}</strong>
                </small>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2">
                @if (! $isPrimary)
                    <form action="{{ route('vendor.products.images.primary', [$product->product_id, $image->product_image_id]) }}" method="POST" class="m-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Đặt làm ảnh đại diện">
                            <i class="bi bi-star me-1"></i>Đặt chính
                        </button>
                    </form>
                @else
                    <span class="text-success small fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i>Ảnh chính
                    </span>
                @endif

                <form action="{{ route('vendor.products.images.destroy', [$product->product_id, $image->product_image_id]) }}" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hình ảnh này khỏi sản phẩm?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Xóa hình ảnh">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
