<?php

namespace App\Http\Requests\Banners;

use App\Models\Banner;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ManageVendorBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');
        $banner = $this->route('banner');

        return $vendor instanceof Vendor
            && $this->user() !== null
            && (int) $vendor->user_id === (int) $this->user()->user_id
            && in_array($vendor->status, ['active', 'approved'], true)
            && ($banner === null || ($banner instanceof Banner && (int) $banner->vendor_id === (int) $vendor->vendor_id));
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(
            redirect()->route('vendors.show', ['vendor' => $this->route('vendor')->vendor_id])
                ->withErrors($validator, $this->errorBag)
                ->withInput($this->only([
                    'banner_editor', 'title', 'alt_text', 'zone', 'link_url',
                    'starts_at', 'expires_at', 'sort_order', 'is_active',
                ]))
                ->with('banner_settings_open', true)
        );
    }

    public function rules(): array
    {
        return [];
    }
}
