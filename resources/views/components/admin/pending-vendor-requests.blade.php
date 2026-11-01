@props([
    'pendingVendorRequests',
    'metrics' => [],
])

<div class="card border-0 shadow-sm rounded-9 p-4 bg-white">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold fg-body mb-1">
                <i class="bi bi-clock-history fg-warning me-2"></i>{{ __('management.vendor_applications_awaiting_approval') }}
            </h2>
            <small class="fg-3">{{ __('management.review_applications_to_activate_selling_access') }}</small>
        </div>
        <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline theme-primary rounded-pill px-3">
            {{ __('management.view_all_count', ['count' => $metrics['pending_vendors'] ?? $pendingVendorRequests->count()]) }}
        </a>
    </div>

    @if ($pendingVendorRequests->isEmpty())
        <div class="text-center py-5 fg-3">
            <i class="bi bi-check2-circle fg-success" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mt-3 fg-body">{{ __('management.no_applications_are_awaiting_approval') }}</h5>
            <p class="small fg-3 mb-0">{{ __('management.all_store_applications_have_been_processed') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-1 small fg-3 text-uppercase">
                    <tr>
                        <th>{{ __('management.registered_store') }}</th>
                        <th>{{ __('management.applicant') }}</th>
                        <th>{{ __('management.submitted_at') }}</th>
                        <th class="text-end">{{ __('management.review_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingVendorRequests as $request)
                        <tr>
                            <td>
                                <div class="fw-bold fg-body">{{ $request->store_name }}</div>
                                <small class="fg-3 text-truncate d-inline-block" style="max-width: 250px;">
                                    {{ $request->description ?: __('management.no_description') }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold fg-body">{{ $request->user->username ?? __('management.n_a') }}</div>
                                <small class="fg-3">{{ $request->user->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="small fg-secondary">
                                    {{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : __('management.n_a') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    {{-- Approve Button --}}
                                    <form action="{{ route('admin.vendors.approve', $request->vendor_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-solid theme-success rounded-pill px-3 fw-semibold shadow-sm" title="{{ __('management.approve_application') }}">
                                            <i class="bi bi-check-lg me-1"></i> {{ __('management.approve') }}
                                        </button>
                                    </form>

                                    {{-- Reject Button --}}
                                    <form action="{{ route('admin.vendors.reject', $request->vendor_id) }}" method="POST" class="d-inline" data-confirm="{{ __('management.reject_this_store_application') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-3" title="{{ __('management.reject_application') }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
