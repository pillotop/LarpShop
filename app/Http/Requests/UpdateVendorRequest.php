<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use App\Queries\Vendors\VendorQuery;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends VendorRegistrationRequest
{
    private ?Vendor $ownedVendor = null;

    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        // Resolve ownership before using the model in the unique-name rule.
        $this->vendor();

        return true;
    }

    public function vendor(): Vendor
    {
        return $this->ownedVendor ??= app(VendorQuery::class)->findOwned(
            $this->user(),
            (int) $this->route('vendor'),
        );
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'store_name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('vendors', 'store_name')->ignore($this->vendor())],
        ]);
    }
}
