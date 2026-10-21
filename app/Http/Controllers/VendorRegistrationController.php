<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRegistrationRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorRegistrationController extends Controller
{
    /**
     * Hiển thị trang đăng ký trở thành vendor hoặc trạng thái nếu đã gửi yêu cầu.
     */
    public function create(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.pages.register', [
            'vendor' => $vendor,
        ]);
    }

    /**
     * Tiếp nhận và lưu thông tin đăng ký vendor.
     */
    public function store(VendorRegistrationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Kiểm tra nếu user đã có hồ sơ vendor
        if ($user->vendor) {
            return redirect()
                ->route('vendor.register')
                ->with('warning', 'Bạn đã có hồ sơ đăng ký vendor trước đó.');
        }

        Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => $request->validated('store_name'),
            'description' => $request->validated('description'),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('vendor.register')
            ->with('success', 'Đơn đăng ký trở thành người bán của bạn đã được gửi thành công và đang chờ xét duyệt!');
    }
}
