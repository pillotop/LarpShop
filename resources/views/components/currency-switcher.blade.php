<form
    action="{{ route('currency.update') }}"
    method="POST"
>
    @csrf

    <fieldset>
        <legend class="h5">{{ __('currency.choose') }}</legend>
        @foreach (config('currency.supported') as $code)
            <label class="d-flex justify-content-between align-items-center border-bottom py-3">
                <span>{{ $code }} — {{ __('currency.' . $code) }}</span>
                <input
                    type="radio"
                    class="radio"
                    name="currency"
                    value="{{ $code }}"
                    @checked($money->selected() === $code)
                >
            </label>
        @endforeach
    </fieldset>

    <p class="small fg-3 mt-3">{{ __('currency.notice') }}</p>
    @if ($money->unavailable())
        <p class="small fg-warning" role="status">{{ __('currency.unavailable') }}</p>
    @endif
    @isset($errors)
        @error('currency')
            <p class="small fg-danger" role="alert">{{ $message }}</p>
        @enderror
    @endisset
    <div class="d-flex justify-content-between align-items-center gap-2">
        <a
            href="https://www.exchangerate-api.com"
            class="small fg-3"
            target="_blank"
            rel="noopener noreferrer"
        >{{ __('currency.attribution') }}</a>
        <button
            type="submit"
            class="btn btn-solid theme-primary rounded-2"
        >{{ __('common.apply') }}</button>
    </div>
</form>
