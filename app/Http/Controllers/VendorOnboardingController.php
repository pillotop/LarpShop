<?php

namespace App\Http\Controllers;

use App\Enums\Vendors\OnboardingStep;
use App\Http\Requests\VendorOnboarding\SaveVendorApplicationRequest;
use App\Http\Requests\VendorOnboarding\SubmitVendorApplicationRequest;
use App\Models\VendorApplication;
use App\Queries\Address\AddressQuery;
use App\Queries\Vendors\VendorApplicationQuery;
use App\Services\Vendors\Onboarding\VendorOnboardingService;
use App\Support\Countries\CountryCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorOnboardingController extends Controller
{
    public function index(Request $request, VendorApplicationQuery $applications): JsonResponse
    {
        return response()->json($applications->forUser($request->user()));
    }

    public function create(Request $request, CountryCatalog $countries, AddressQuery $addresses): JsonResponse|View
    {
        $defaults = [
            'contact_name' => $request->user()->username,
            'contact_email' => $request->user()->email,
            'contact_phone' => $request->user()->phone_number,
            'current_step' => OnboardingStep::Seller->value,
        ];
        if (! $request->expectsJson()) {
            return $this->page($request, null, $defaults, $countries, $addresses);
        }

        return response()->json([
            'defaults' => $defaults,
            'countries' => $countries->all(),
            'steps' => array_map(fn ($step) => $step->value, OnboardingStep::cases()),
        ]);
    }

    public function show(Request $request, int $application, VendorApplicationQuery $applications, CountryCatalog $countries, AddressQuery $addresses): JsonResponse|View
    {
        $application = $applications->findOwned($request->user(), $application)->load('destinations');

        return $request->expectsJson()
            ? response()->json(['application' => $application])
            : $this->page($request, $application, [], $countries, $addresses);
    }

    private function page(Request $request, ?VendorApplication $application, array $defaults, CountryCatalog $countries, AddressQuery $addresses): View
    {
        $countryNames = $countries->all();
        $savedAddresses = $addresses->forUser($request->user());
        $fields = array_fill_keys((new VendorApplication)->getFillable(), '');
        $values = $application?->only(array_keys($fields)) ?? $defaults;
        $onboarding = [
            'form' => array_replace($fields, $values, [
                'destination_countries' => $application?->destinations->pluck('country_code')->all() ?? [],
            ]),
            'applicationId' => $application?->getKey(),
            'editable' => $application?->status->isEditable() ?? true,
            'imageUrl' => $application?->profile_image_url ?? asset('images/default-vendor.svg'),
            'defaultImageUrl' => asset('images/default-vendor.svg'),
            'countries' => $countryNames,
            'addresses' => $savedAddresses->mapWithKeys(fn ($address) => [(string) $address->getKey() => $address->snapshot()]),
            'urls' => [
                'store' => route('vendor-applications.store'),
                'update' => route('vendor-applications.update', '__id__'),
                'show' => route('vendor-applications.show', '__id__'),
                'submit' => route('vendor-applications.submit', '__id__'),
                'profile' => route('profile'),
            ],
            'messages' => __('onboarding.client'),
            'stepLabels' => __('onboarding.steps'),
            'choiceLabels' => [
                'seller_type' => __('onboarding.seller_types'),
                'delivery_mode' => __('onboarding.delivery_modes'),
            ],
            'steps' => array_map(fn ($step) => $step->value, OnboardingStep::cases()),
        ];

        return view('vendor.onboarding.show', compact('application', 'onboarding', 'countryNames', 'savedAddresses'));
    }

    public function store(SaveVendorApplicationRequest $request, VendorOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'message' => __('onboarding.saved'),
            'application' => $onboarding->save($request->user(), $request->validated()),
        ], 201);
    }

    public function update(SaveVendorApplicationRequest $request, int $application, VendorOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'message' => __('onboarding.saved'),
            'application' => $onboarding->save($request->user(), $request->validated(), $application),
        ]);
    }

    public function submit(SubmitVendorApplicationRequest $request, int $application, VendorOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'message' => __('onboarding.submitted'),
            'application' => $onboarding->submit($request->user(), $application),
        ]);
    }
}
