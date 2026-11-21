@php
    $locale = app()->getLocale();
    $countryCode = $locale === 'vi' ? 'VN' : 'GB';
    $language = config('app.supported_locales')[$locale] ?? $locale;
@endphp

<button
    type="button"
    class="language-trigger d-inline-flex align-items-center gap-2 border-0 bg-transparent p-2"
    x-data
    x-on:click="$dispatch('open-settings', { section: 'language' })"
    data-bs-toggle="drawer"
    data-bs-target="#settings-panel"
    aria-haspopup="dialog"
    aria-controls="settings-panel"
    aria-label="{{ __('common.language') }}: {{ $language }}"
    title="{{ __('common.language') }}: {{ $language }}"
>
    <span class="language-trigger-flag rounded-circle overflow-hidden border" aria-hidden="true">
        <svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" focusable="false">
            @if ($locale === 'vi')
                <path fill="#da251d" d="M0 0h60v40H0z" />
                <path fill="#ff0" d="m30 7 3 9.2h9.7l-7.9 5.7 3 9.3-7.8-5.7-7.8 5.7 3-9.3-7.9-5.7H27z" />
            @else
                <path fill="#012169" d="M0 0h60v40H0z" />
                <path stroke="#fff" stroke-width="8" d="m0 0 60 40M60 0 0 40" />
                <path stroke="#c8102e" stroke-width="3" d="m0 0 60 40M60 0 0 40" />
                <path stroke="#fff" stroke-width="13" d="M30 0v40M0 20h60" />
                <path stroke="#c8102e" stroke-width="8" d="M30 0v40M0 20h60" />
            @endif
        </svg>
    </span>
    <span class="small fw-semibold">{{ $countryCode }}</span>
</button>
