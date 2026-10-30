@props(['product', 'id'])

@php
    $restore = old('review_form') === $id;
    $formErrors = $restore ? $errors->review : new \Illuminate\Support\MessageBag;
@endphp

<details
    id="{{ $id }}"
    class="border rounded-3 p-3 mt-3"
    @if ($restore && $formErrors->any()) open @endif
>
    <summary class="btn btn-outline-primary rounded-pill px-4">Write a review</summary>

    <form
        action="{{ route('reviews.store', $product) }}"
        method="POST"
        class="mt-3"
    >
        @csrf
        <input type="hidden" name="review_form" value="{{ $id }}">
        <p>Review for <strong>{{ $product->name }}</strong></p>

        @if ($formErrors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($formErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label for="{{ $id }}-rating" class="form-label">Rating</label>
            <select
                id="{{ $id }}-rating"
                name="rating"
                class="form-select"
                required
            >
                <option value="">Choose a rating</option>
                @foreach ([5, 4, 3, 2, 1] as $rating)
                    <option
                        value="{{ $rating }}"
                        @selected($restore && (string) old('rating') === (string) $rating)
                    >
                        {{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="{{ $id }}-comment" class="form-label">Your review (optional)</label>
            <textarea
                id="{{ $id }}-comment"
                name="comment"
                class="form-control"
                rows="4"
                maxlength="2000"
                aria-describedby="{{ $id }}-help"
            >{{ $restore ? old('comment') : '' }}</textarea>
            <div id="{{ $id }}-help" class="form-text">
                Share your experience with this product. Up to 2,000 characters.
            </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4">
            Submit review
        </button>
    </form>
</details>
