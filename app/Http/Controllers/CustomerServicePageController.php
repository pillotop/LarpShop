<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class CustomerServicePageController extends Controller
{
    public function help(): View
    {
        return $this->page('help');
    }

    public function privacy(): View
    {
        return $this->page('privacy');
    }

    public function community(): View
    {
        return $this->page('community');
    }

    public function recalls(): View
    {
        return $this->page('recalls');
    }

    /**
     * Render a customer service static page with the shared navigation data.
     */
    private function page(string $name): View
    {
        return view('static.customer-service.'.$name, [
            'productCategories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
