<?php

namespace App\Http\Requests\VendorOnboarding;

use App\Queries\Vendors\VendorApplicationQuery;
use Illuminate\Foundation\Http\FormRequest;

class SubmitVendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }
        app(VendorApplicationQuery::class)->findOwned($this->user(), (int) $this->route('application'));

        return true;
    }

    public function rules(): array
    {
        return ['confirmation' => ['required', 'accepted']];
    }
}
