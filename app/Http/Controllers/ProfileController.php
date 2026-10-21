<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's account profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $user->load('vendor');

        $vendor = $user->vendor;
        $isVendor = $user->isVendor();
        $vendorProductCount = $vendor ? $vendor->products()->count() : 0;
        $productCategories = Category::all();

        return view('profile.pages.show', [
            'user' => $user,
            'vendor' => $vendor,
            'isVendor' => $isVendor,
            'vendorProductCount' => $vendorProductCount,
            'productCategories' => $productCategories,
        ]);
    }

    /**
     * Update the user's personal profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->user_id, 'user_id'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:15',
            ],
        ], [
            'username.required' => 'Tên người dùng không được để trống.',
            'username.unique' => 'Tên người dùng này đã được sử dụng.',
            'username.min' => 'Tên người dùng phải có ít nhất 3 ký tự.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng bởi tài khoản khác.',
            'phone_number.max' => 'Số điện thoại không được vượt quá 15 ký tự.',
        ]);

        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cập nhật thông tin cá nhân thành công!',
                'user' => $user,
            ]);
        }

        return redirect()->route('profile')
            ->with('status', 'profile-updated')
            ->with('success', 'Cập nhật thông tin tài khoản thành công!');
    }

    /**
     * Update the vendor store profile information.
     */
    public function updateVendor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isVendor() || !$user->vendor) {
            abort(403, 'Bạn chưa có gian hàng vendor để cập nhật.');
        }

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'store_name.required' => 'Tên cửa hàng không được để trống.',
            'store_name.min' => 'Tên cửa hàng phải có ít nhất 2 ký tự.',
            'store_name.max' => 'Tên cửa hàng không được vượt quá 100 ký tự.',
            'description.max' => 'Mô tả cửa hàng không được vượt quá 1000 ký tự.',
        ]);

        $vendor = $user->vendor;
        $vendor->store_name = $validated['store_name'];
        $vendor->description = $validated['description'] ?? null;
        $vendor->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cập nhật hồ sơ gian hàng thành công!',
                'vendor' => $vendor,
            ]);
        }

        return redirect()->route('profile')
            ->with('status', 'vendor-updated')
            ->with('success', 'Hồ sơ gian hàng đã được cập nhật thành công!');
    }

    /**
     * Register a new vendor store for a customer.
     */
    public function registerVendor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->vendor) {
            return redirect()->route('profile')
                ->with('warning', 'Bạn đã có gian hàng trên hệ thống.');
        }

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'store_name.required' => 'Tên cửa hàng không được để trống.',
            'store_name.min' => 'Tên cửa hàng phải có ít nhất 2 ký tự.',
            'store_name.max' => 'Tên cửa hàng không được vượt quá 100 ký tự.',
            'description.max' => 'Mô tả cửa hàng không được vượt quá 1000 ký tự.',
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => $validated['store_name'],
            'description' => $validated['description'] ?? null,
            'status' => 'active',
        ]);

        $user->role = 'vendor';
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đăng ký gian hàng thành công!',
                'vendor' => $vendor,
            ], 201);
        }

        return redirect()->route('profile')
            ->with('status', 'vendor-registered')
            ->with('success', 'Chúc mừng bạn đã mở gian hàng thành công!');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không chính xác.',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đổi mật khẩu thành công!',
            ]);
        }

        return redirect()->route('profile')
            ->with('status', 'password-updated')
            ->with('success', 'Mật khẩu của bạn đã được thay đổi thành công!');
    }
}
