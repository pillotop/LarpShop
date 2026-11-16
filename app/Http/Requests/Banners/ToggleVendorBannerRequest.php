<?php

namespace App\Http\Requests\Banners;

class ToggleVendorBannerRequest extends ManageVendorBannerRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
