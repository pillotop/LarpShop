<?php

namespace App\Http\Requests\Banners;

use Illuminate\Validation\Rule;

class SaveVendorBannerRequest extends ManageVendorBannerRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->input('is_active', false),
            'banner_editor' => $this->route('banner') ? (string) $this->route('banner')->banner_id : 'new',
        ]);
    }

    public function rules(): array
    {
        $zones = array_keys(array_filter(config('banners.zones'), fn ($zone) => $zone['context'] === 'vendor'));

        return [
            'title' => ['required', 'string', 'max:255'],
            'alt_text' => ['required', 'string', 'max:255'],
            'zone' => ['required', Rule::in($zones)],
            'link_url' => ['nullable', 'url:http,https', 'max:2048'],
            'starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'expires_at' => ['nullable', 'date_format:Y-m-d\TH:i', Rule::when($this->filled('starts_at'), 'after:starts_at')],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
            'banner_image' => [
                $this->route('banner') === null ? 'required' : 'nullable',
                'file', 'mimes:png,webp', 'max:'.(int) (config('banners.upload.max_cropped_bytes') / 1024),
            ],
        ];
    }
}
