<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('homepage', [
            'productCategories' => Category::query()->get(),
        ]);
    }
}
