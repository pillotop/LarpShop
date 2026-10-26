<?php

namespace App\Http\Controllers;

use App\Http\Requests\Address\SaveAddressRequest;
use App\Models\Address;
use App\Queries\Address\AddressQuery;
use App\Services\Addresses\AddressService;
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
    ): RedirectResponse {
        $this->service->create(
            $request->user(),
            $request->validated(),
        );

        return redirect()->to(route('profile').'#pane-billing')->with('success', 'Address saved.');
    }

    public function update(
        SaveAddressRequest $request,
        Address $address,
    ): RedirectResponse {
        $address = $this
            ->query
            ->findOwnedOrFail($request->user(), $address->getKey());

        $this->service->update($request->user(), $address, $request->validated());

        return redirect()->to(route('profile').'#pane-billing')->with('success', 'Address updated.');
    }

    public function setDefault(
        Request $request,
        Address $address,
    ): RedirectResponse {
        $this->service->setDefault($request->user(), $address);

        return redirect()->to(route('profile').'#pane-billing')->with('success', 'Default address updated.');
    }

    public function destroy(
        Request $request,
        Address $address,
    ): RedirectResponse {
        $this->service->delete($request->user(), $address);

        return redirect()->to(route('profile').'#pane-billing')->with('success', 'Address removed.');
    }
}
