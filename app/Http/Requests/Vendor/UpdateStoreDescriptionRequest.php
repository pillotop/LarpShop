<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $vendor->user_id === $this->user()?->user_id
            && in_array($vendor->status, ['active', 'approved'], true);
    }

    public function rules(): array
    {
        return [
            'description' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }
}
