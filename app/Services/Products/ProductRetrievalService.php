<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Database\Eloquent\Collection;

class ProductRetrievalService
{
    public function __construct(private ProductCatalogQuery $productCatalogQuery)
    {}

    public function getProducts(
        ?int $categoryId = null,
        int $limit = 12,
        bool $inStockOnly = true,
    ): Collection {
        return $this->productCatalogQuery->getProducts(
            categoryId: $categoryId,
            limit: $limit,
            inStockOnly: $inStockOnly,
        );
    }

    public function getProductDetails(int $productId): ?Product
    {
        return $this->productCatalogQuery->getProductDetails($productId);
    }

    public function getSimilarProducts(
        int $productId,
        ?int $categoryId = null,
        int $limit = 6,
    ): Collection {
        return $this->productCatalogQuery->getSimilarProducts(
            productId: $productId,
            categoryId: $categoryId,
            limit: $limit,
        );
    }
}
