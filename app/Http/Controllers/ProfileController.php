<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Queries\Address\AddressQuery;
use App\Queries\Categories\CategoryQuery;
use App\Queries\Vendors\VendorQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, VendorQuery $vendors, CategoryQuery $categories, AddressQuery $addresses): View
    {
        return view('profile.pages.show', [
            'user' => $request->user(),
            'vendors' => $vendors->forUser($request->user()),
            'addresses' => $addresses->forUser($request->user()),
            'productCategories' => $categories->forNavigation(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $user->update([
            'username' => $data['username'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Profile updated successfully.', 'user' => $user]);
        }

        return redirect()->route('profile')
            ->with('status', 'profile-updated')
            ->with('success', 'Profile updated successfully.');
    }
}
