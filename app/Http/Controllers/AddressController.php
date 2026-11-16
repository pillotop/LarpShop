<?php

namespace App\Http\Controllers;

use App\Http\Requests\Address\SaveAddressRequest;
use App\Models\Address;
use App\Queries\Address\AddressQuery;
use App\Services\Addresses\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private AddressQuery $query,
        private AddressService $service,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->to(route('profile').'#pane-billing');
    }

    public function store(
        SaveAddressRequest $request,
    ): RedirectResponse|JsonResponse {
        $address = $this->service->create(
            $request->user(),
            $request->validated(),
        );

        if ($request->expectsJson()) {
            $suffix = $address->is_default ? __('checkout.address.default') : '';

            return response()->json([
                'success' => true,
                'message' => __('profile.success.address_saved'),
                'address' => [
                    'address_id' => $address->getKey(),
                    'is_default' => (bool) $address->is_default,
                    'label' => "{$address->recipient_name} — {$address->address_line_1}, {$address->city}{$suffix}",
                ],
            ], 201);
        }

        return redirect()->to(route('profile').'#pane-billing')->with('success', __('profile.success.address_saved'));
    }

    public function update(
        SaveAddressRequest $request,
        Address $address,
    ): RedirectResponse {
        $address = $this
            ->query
            ->findOwnedOrFail($request->user(), $address->getKey());

        $this->service->update($request->user(), $address, $request->validated());

        return redirect()->to(route('profile').'#pane-billing')->with('success', __('profile.success.address_updated'));
    }

    public function setDefault(
        Request $request,
        Address $address,
    ): RedirectResponse {
        $this->service->setDefault($request->user(), $address);

        return redirect()->to(route('profile').'#pane-billing')->with('success', __('profile.success.address_default'));
    }

    public function destroy(
        Request $request,
        Address $address,
    ): RedirectResponse {
        $this->service->delete($request->user(), $address);

        return redirect()->to(route('profile').'#pane-billing')->with('success', __('profile.success.address_removed'));
    }
}
