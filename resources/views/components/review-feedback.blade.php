@if (session('review_success'))
    <div id="review-feedback" class="alert theme-success" role="status">
        {{ session('review_success') }}
    </div>
@endif

@error('eligibility', 'review')
    <div class="alert theme-warning" role="alert">
        {{ $message }}
    </div>
@enderror
