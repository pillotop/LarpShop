<h1>Products</h1>
<section style="margin: 20px 0;">
    <form action="{{ route('products.index') }}" method="GET">
        <label for="product-search">Tìm kiếm sản phẩm</label>

        <input
            type="search"
            id="product-search"
            name="q"
            value="{{ $keyword ?? '' }}"
            placeholder="Nhập tên hoặc từ khóa..."
            maxlength="100"
            style="padding: 10px; width: 300px;"
        >

        <button type="submit">Tìm kiếm</button>

        <a href="{{ route('products.index') }}">
            Xóa bộ lọc
        </a>
    </form>

    @error('q')
        <p style="color: red;">{{ $message }}</p>
    @enderror

    @if (!empty($keyword))
        <p>
            Kết quả tìm kiếm cho:
            <strong>{{ $keyword }}</strong>
        </p>
    @endif

    <p>Tìm thấy {{ $products->count() }} sản phẩm.</p>
</section>

@if ($products->isEmpty())
    <p>Không tìm thấy sản phẩm phù hợp.</p>
@endif
@foreach ($products as $product)
    <article>
        <h2>{{ $product->name }}</h2>
        <p>{{ $product->description }}</p>
        <p>Price: {{ number_format($product->price, 2) }}</p>
        <p>Category: {{ $product->category->name }}</p>
    </article>

    <h3>Upload an image</h3>
    <form
        action="{{ route('products.images.store', $product) }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <label for="image-{{ $product->product_id }}">
            Image file
        </label>

        <input
            id="image-{{ $product->product_id }}"
            type="file"
            name="image"
            accept="image/*"
            required
        >

        <label for="alt-{{ $product->product_id }}">
            Alternativetext
        </label>

        <input
            id="alt-{{ $product->product_id }}"
            type="text"
            name="alt_text"
            maxlength="255"
        >

        <button type="submit">Upload</button>
    </form>

    @foreach ($product->images as $image)
        <img
            src="{{ asset("storage/" . $image->path) }}"
            alt="{{ $image->alt_text ?? $product->name }}"
            style="max-width: 200px; height: auto;"
        >
    @endforeach
@endforeach
