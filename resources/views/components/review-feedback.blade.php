@if (session('review_success'))
    <div id="review-feedback" class="alert alert-success" role="status">
        {{ session('review_success') }}
    </div>
@endif

@error('eligibility', 'review')
    <div class="alert alert-warning" role="alert">
        {{ $message }}
    </div>
@enderror
