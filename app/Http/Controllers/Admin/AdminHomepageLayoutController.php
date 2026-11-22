<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Promotions\HomepageLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminHomepageLayoutController extends Controller
{
    public function index(HomepageLayoutService $layout): View
    {
        $blocks = $layout->managedBlocks();

        return view('admin.sections.homepage-layout', ['blocks' => $blocks, 'revision' => $layout->revision($blocks)]);
    }

    public function update(Request $request, HomepageLayoutService $layout): RedirectResponse
    {
        $data = $request->validate([
            'revision' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'positions' => ['required', 'array', 'max:500'],
            'positions.*' => ['required', 'integer', 'between:0,1000000', 'distinct'],
        ]);
        $layout->save($data['positions'], $data['revision']);

        return to_route('admin.homepage-layout.index')->with('success', __('homepage_layout.saved'));
    }
}
