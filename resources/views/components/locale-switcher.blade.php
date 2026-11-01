<form
    action="{{ route('locale.update') }}"
    method="POST"
    {{ $attributes }}
>
    @csrf

    <fieldset>
        <legend class="h5">{{ __('common.choose_language') }}</legend>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">{{ __('common.language') }}</th>
                    <th scope="col" class="text-end">{{ __('common.selection') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (config('app.supported_locales') as $code => $name)
                    <tr>
                        <td lang="{{ str_replace('_', '-', $code) }}">
                            {{ $name }}
                        </td>
                        <td class="text-end">
                            <input
                                type="radio"
                                class="radio"
                                name="locale"
                                value="{{ $code }}"
                                aria-label="{{ $name }}"
                                @checked(app()->getLocale() === $code)
                            >
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </fieldset>

    <div class="d-flex justify-content-end">
        <button
            type="submit"
            class="btn btn-solid theme-primary rounded-pill"
        >
            {{ __('common.apply') }}
        </button>
    </div>
</form>
