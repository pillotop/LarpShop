@props([
    'vendors',
])

@if ($vendors->isEmpty())
    <div class="text-center py-5">
        <div class="bg-1 d-inline-flex p-4 mb-3 fg-3">
            <i class="bi bi-inbox fs-3xl"></i>
        </div>
        <h5 class="fw-bold fg-body">{{ __('management.no_records_match_this_filter') }}</h5>
        <p class="fg-3 small">{{ __('management.switch_statuses_to_view_other_stores') }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
                <tr>
                    <th style="width: 50px;">{{ __('management.id_text') }}</th>
                    <th>{{ __('management.store_information') }}</th>
                    <th>{{ __('management.account_owner') }}</th>
                    <th>{{ __('management.submitted_at') }}</th>
                    <th>{{ __('management.status') }}</th>
                    <th class="text-end" style="min-width: 220px;">{{ __('management.administrative_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vendors as $vendor)
                    <tr>
                        <td class="fg-3 fw-bold">#{{ $vendor->vendor_id }}</td>
                        <td>
                            <div class="fw-bold fg-body fs-md">{{ $vendor->store_name }}</div>
                            <small class="fg-2 d-block mt-1" style="max-width: 320px;">
                                {{ $vendor->description ?: __('management.no_store_description_yet') }}
                            </small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-10 fg-primary p-2 d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                                    {{ strtoupper(substr($vendor->user->username ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold fg-body">{{ $vendor->user->username ?? __('management.n_a') }}</div>
                                    <small class="fg-3 d-block">{{ $vendor->user->email ?? '' }}</small>
                                    @if ($vendor->user?->phone_number)
                                        <small class="fg-3"><i class="bi bi-telephone me-1"></i>{{ $vendor->user->phone_number }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold fg-body">
                                {{ ($vendor->application?->submitted_at ?? $vendor->created_at)?->format('d/m/Y') ?? __('management.n_a') }}
                            </div>
                            <small class="fg-3">
                                {{ ($vendor->application?->submitted_at ?? $vendor->created_at)?->format('H:i') }}
                            </small>
                        </td>
                        <td>
                            @if ($vendor->status === 'pending')
                                <span class="badge bg-subtle-warning fg-emphasis-warning border border-warning px-3 py-1 fw-bold">
                                    <i class="bi bi-hourglass-split me-1"></i> {{ __('management.pending') }}
                                </span>
                            @elseif (in_array($vendor->status, ['active', 'approved']))
                                <span class="badge bg-1 fg-2 border border px-3 py-1 fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('management.approved') }}
                                </span>
                            @elseif ($vendor->status === 'rejected')
                                <span class="badge bg-1 fg-2 border border px-3 py-1 fw-bold">
                                    <i class="bi bi-x-circle-fill me-1"></i> {{ __('management.rejected') }}
                                </span>
                            @elseif ($vendor->status === 'suspended')
                                <span class="badge bg-1 fg-2 border border px-3 py-1 fw-bold">
                                    <i class="bi bi-slash-circle me-1"></i> {{ __('management.suspended') }}
                                </span>
                            @else
                                <span class="badge bg-1 fg-body border px-3 py-1">
                                    {{ __('products.vendor_status.'.$vendor->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2 align-items-center">
                                <a
                                    href="{{ route('admin.vendors.show', $vendor) }}"
                                    class="btn btn-sm btn-outline theme-primary "
                                >{{ __('onboarding.review.details') }}</a>
                                @if (in_array($vendor->status, ['active', 'approved', 'suspended'], true))
                                    <a
                                        href="{{ route('admin.vendors.edit', $vendor) }}"
                                        class="btn btn-sm btn-outline theme-inverse "
                                    >{{ __('management.store_configuration') }}</a>
                                @endif
                                @if (in_array($vendor->status, ['active', 'approved']))
                                    {{-- View Storefront Link --}}
                                    <a href="{{ route('vendors.show', $vendor->vendor_id) }}" target="_blank" class="btn btn-sm btn-outline theme-primary px-2" title="{{ __('management.view_store') }}">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>

                                    {{-- UC-40: Suspend Active Vendor --}}
                                    <form action="{{ route('admin.vendors.suspend', $vendor->vendor_id) }}" method="POST" class="m-0" data-confirm="{{ __('management.suspend_this_store') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline theme-inverse px-3 d-inline-flex align-items-center gap-1" title="{{ __('management.suspended') }}">
                                            <i class="bi bi-slash-circle"></i>
                                            <span>{{ __('management.suspended') }}</span>
                                        </button>
                                    </form>
                                @elseif ($vendor->status === 'suspended')
                                    {{-- UC-40: Reinstate/Re-approve Vendor --}}
                                    <form action="{{ route('admin.vendors.reinstate', $vendor->vendor_id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline theme-inverse px-3 fw-semibold d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span>{{ __('management.approve_again_reinstate') }}</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination Links --}}
    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small fg-3">
            {{ __('pagination.summary', ['first' => $vendors->firstItem(), 'last' => $vendors->lastItem(), 'total' => $vendors->total()]) }}
        </div>
        <div>
            {{ $vendors->links('shared.pagination') }}
        </div>
    </div>
@endif
