<?php

namespace App\View\Composers;

use App\Services\Categories\CategoryPreviewService;
use Illuminate\View\View;

class CategoryDropdownComposer
{
    public function __construct(private CategoryPreviewService $previews) {}

    public function compose(View $view): void
    {
        $view->with('previews', $this->previews->forCategories($view->getData()['categories']));
    }
}
