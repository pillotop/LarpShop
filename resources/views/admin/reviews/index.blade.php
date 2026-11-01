@extends('layouts.admin')

@section('title', __('management.review_moderation').' (UC-43)')
@section('page_title', __('management.review_moderation'))

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <div class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-chat-square-quote fg-primary me-2"></i>{{ __('management.review_moderation') }}</h1>
            <p class="fg-3 small mb-0">{{ __('management.find_and_remove_inappropriate_or_fraudulent_product_reviews') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.reviews.index') }}" class="row g-2 mb-4">
            <div class="col-12 md:col-6">
                <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="{{ __('management.search_comment_reviewer_or_product') }}">
            </div>
            <div class="col-12 md:col-3">
                <select name="rating" class="form-control">
                    <option value="">{{ __('management.all_ratings') }}</option>
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((int) $rating === $i)>{{ $i }} {{ $i === 1 ? 'star' : 'stars' }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-12 md:col-3 d-flex gap-2">
                <button type="submit" class="btn btn-solid theme-inverse flex-grow-1">{{ __('management.filter') }}</button>
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline theme-secondary">{{ __('management.reset') }}</a>
            </div>
        </form>

        @if ($reviews->isEmpty())
            <div class="text-center py-5 fg-3">
                <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
                {{ __('management.no_reviews_match_your_filters') }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-1 text-uppercase small fg-3">
                        <tr>
                            <th>{{ __('management.reviewer') }}</th>
                            <th>{{ __('management.product') }}</th>
                            <th>{{ __('management.rating') }}</th>
                            <th>{{ __('management.comment') }}</th>
                            <th>{{ __('management.posted') }}</th>
                            <th class="text-end">{{ __('management.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>
                                    <div class="fw-semibold fg-body">{{ $review->user?->username ?? __('management.deleted_user') }}</div>
                                    <small class="fg-3">{{ $review->user?->email }}</small>
                                </td>
                                <td>
                                    <div>{{ $review->product?->name ?? __('management.deleted_product') }}</div>
                                    <small class="fg-3">{{ $review->product?->vendor?->store_name }}</small>
                                </td>
                                <td class="fg-warning text-nowrap">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </td>
                                <td style="max-width: 360px;" class="text-break">{{ $review->comment }}</td>
                                <td class="small fg-3">{{ $review->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review->review_id) }}" class="m-0" data-confirm="{{ __('management.permanently_delete_this_review') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-3">{{ __('management.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="small fg-3">
                    {{ __('pagination.summary', ['first' => $reviews->firstItem(), 'last' => $reviews->lastItem(), 'total' => $reviews->total()]) }}
                </div>
                <div>{{ $reviews->links('shared.pagination') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
