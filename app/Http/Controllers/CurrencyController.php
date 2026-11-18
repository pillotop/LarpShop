<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CurrencyController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', Rule::in(config('currency.supported'))],
        ]);

        $request->session()->put('display_currency', $validated['currency']);

        return back();
    }
}
