<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    /**
     * Get active product categories for the navigation bar.
     */
    private function getProductCategories()
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Show About Us page.
     */
    public function about(): View
    {
        return view('static.company.about', [
            'productCategories' => $this->getProductCategories(),
        ]);
    }

    /**
     * Show Contact Us page.
     */
    public function contact(): View
    {
        return view('static.company.contact', [
            'productCategories' => $this->getProductCategories(),
        ]);
    }

    /**
     * Handle Contact form submission.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'in:support,orders,vendor,partnership,general'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        return redirect()->route('contact')
            ->with('success', 'Thank you! Your message has been dispatched to our courier hawks. We will reply within 1-2 business days.');
    }

    /**
     * Show Investor Relations page.
     */
    public function investors(): View
    {
        return view('static.company.investors', [
            'productCategories' => $this->getProductCategories(),
        ]);
    }

    /**
     * Show Blog page.
     */
    public function blog(): View
    {
        return view('static.company.blog', [
            'productCategories' => $this->getProductCategories(),
        ]);
    }

    /**
     * Show Press & Media Room page.
     */
    public function press(): View
    {
        return view('static.company.press', [
            'productCategories' => $this->getProductCategories(),
        ]);
    }
}
