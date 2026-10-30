<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Đăng sản phẩm mới - Larp Shop</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow-sm p-4">
                <h3 class="mb-4">Thêm sản phẩm mới (Vendor Dashboard)</h3>
                <form action="{{ route('vendor.product.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control" required placeholder="Nhập tên sản phẩm...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giá bán ($)</label>
                        <input type="number" name="price" class="form-control" required placeholder="Ví dụ: 99.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="1">Collectibles</option>
                            <option value="2">Electronics</option>
                            <option value="3">Fashion</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh sản phẩm</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Đăng sản phẩm</button>
                </form>
            </div>
        </div>
    </body>
</html>
