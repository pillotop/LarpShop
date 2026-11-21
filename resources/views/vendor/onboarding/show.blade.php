@extends('layouts.app')

@section('title', __('onboarding.title').' · LarpShop')

@section('head')
    @vite(['resources/css/vendor_onboarding.css', 'resources/css/components/loading_state.css'])
@endsection

@section('content')
    <script type="application/json" id="vendor-onboarding-data">@json($onboarding)</script>

    <div
        class="vendor-onboarding"
        x-data="vendorOnboarding(JSON.parse(document.getElementById('vendor-onboarding-data').textContent))"
        @beforeunload.window="warnBeforeLeaving($event)"
    >
        <header class="border-bottom">
            <div class="onboarding-shell d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-3">
                <a href="{{ route('home') }}" class="h5 fw-bold mb-0 text-decoration-none fg-body">LarpShop</a>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('profile') }}#pane-vendor" class="fg-2">{{ __('onboarding.back_stores') }}</a>
                    @if ($onboarding['editable'])
                        <button
                            type="button"
                            class="btn btn-outline theme-primary rounded-2"
                            @click="save('exit')"
                            :disabled="busy || uncertainCreate"
                            x-cloak
                        >
                            {{ __('onboarding.save_exit') }}
                        </button>
                    @endif
                </div>
            </div>
        </header>

        <main class="onboarding-shell py-4 px-3">
            <h1 class="h3 fw-bold mb-2">{{ __('onboarding.title') }}</h1>
            <p class="fg-3 mb-4">{{ __('onboarding.intro') }}</p>
            <noscript><p>{{ __('onboarding.javascript_required') }}</p></noscript>

            <div x-cloak>
                @if ($application?->rejection_reason)
                    <aside class="border rounded-2 p-3 mb-4">
                        <h2 class="h6 fw-bold">{{ __('onboarding.review_feedback') }}</h2>
                        <p class="mb-0">{{ $application->rejection_reason }}</p>
                    </aside>
                @endif

                @if ($onboarding['editable'])
                    <nav class="onboarding-progress mb-4" aria-label="{{ __('onboarding.progress') }}">
                        <ol class="list-unstyled mb-0">
                            @foreach (\App\Enums\Vendors\OnboardingStep::cases() as $step)
                                <li>
                                    <button
                                        type="button"
                                        class="onboarding-step-button"
                                        @click="backTo({{ $loop->index }})"
                                        :class="{ 'is-current': stepIndex === {{ $loop->index }} }"
                                        :aria-current="stepIndex === {{ $loop->index }} ? 'step' : null"
                                        :disabled="busy || {{ $loop->index }} > stepIndex"
                                    >
                                        <span class="onboarding-step-number" aria-hidden="true">{{ $loop->iteration }}</span>
                                        {{ __('onboarding.steps.'.$step->value) }}
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @else
                    <p class="border rounded-2 p-3" role="status">
                        {{ __('onboarding.status.'.$application->status->value) }} — {{ __('onboarding.read_only') }}
                    </p>
                @endif

                <div
                    x-ref="feedback"
                    x-show="notice || errorMessages.length"
                    class="border border-danger rounded-2 p-3 mb-4"
                    role="alert"
                    tabindex="-1"
                >
                    <p class="mb-1" x-text="notice"></p>
                    <ul class="mb-0">
                        <template x-for="(message, index) in errorMessages" :key="index">
                            <li x-text="message"></li>
                        </template>
                    </ul>
                    <a
                        href="{{ route('profile') }}#pane-vendor"
                        x-show="uncertainCreate"
                    >{{ __('onboarding.check_drafts') }}</a>
                </div>

                <div class="mb-3" x-show="busy" x-cloak>
                    <x-loading-state :label="__('onboarding.saving')" />
                </div>

                <h2 class="h4 fw-semibold mb-3" tabindex="-1" x-ref="stepHeading" x-text="stepLabels[currentStep]"></h2>

                @if ($onboarding['editable'])
                    <form
                        method="POST"
                        action="{{ $application ? route('vendor-applications.update', $application) : route('vendor-applications.store') }}"
                        enctype="multipart/form-data"
                        novalidate
                        @submit.prevent="currentStep === 'review' ? submit() : save('next')"
                        @input="markDirty($event)"
                        @change="markDirty($event)"
                        :aria-busy="busy"
                    >
                        @csrf
                        <fieldset :disabled="busy || uncertainCreate" class="onboarding-form-fields">
                            <legend class="visually-hidden">{{ __('onboarding.title') }}</legend>
                            <div class="border rounded-2 p-3 md:p-4">
                                @foreach (\App\Enums\Vendors\OnboardingStep::cases() as $step)
                                    <section
                                        x-show="currentStep === '{{ $step->value }}'"
                                        class="onboarding-step"
                                        x-transition:enter="onboarding-enter"
                                        x-transition:enter-start="onboarding-enter-start"
                                        x-transition:enter-end="onboarding-enter-end"
                                    >
                                        @if ($step === \App\Enums\Vendors\OnboardingStep::Review)
                                            <p class="fg-3">{{ __('onboarding.review_intro') }}</p>
                                            <x-vendor-onboarding.summary />
                                            <div class="mt-4">
                                                <label class="d-flex align-items-start gap-2" for="onboarding-confirmation">
                                                    <input
                                                        id="onboarding-confirmation"
                                                        type="checkbox"
                                                        class="checkbox flex-shrink-0 mt-1"
                                                        x-ref="confirmation"
                                                        x-model="confirmation"
                                                        :aria-invalid="Boolean(fieldError('confirmation'))"
                                                        aria-describedby="onboarding-confirmation-error"
                                                    >
                                                    {{ __('onboarding.confirmation') }}
                                                </label>
                                                <p id="onboarding-confirmation-error" class="small text-danger" x-show="fieldError('confirmation')" x-text="fieldError('confirmation')"></p>
                                            </div>
                                        @else
                                            <x-dynamic-component
                                                :component="'vendor-onboarding.steps.'.$step->value"
                                                :countries="$countryNames"
                                                :addresses="$savedAddresses"
                                            />
                                        @endif
                                    </section>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap justify-content-between gap-3 mt-4">
                                <button
                                    type="button"
                                    class="btn btn-outline theme-primary rounded-2"
                                    @click="backTo(stepIndex - 1)"
                                    :disabled="stepIndex === 0"
                                >{{ __('onboarding.back') }}</button>
                                <button type="submit" class="btn btn-solid theme-primary rounded-2">
                                    <span x-show="currentStep !== 'review'">{{ __('onboarding.next') }}</span>
                                    <span x-show="currentStep === 'review'">{{ __('onboarding.submit') }}</span>
                                </button>
                            </div>
                        </fieldset>
                    </form>
                @else
                    <x-vendor-onboarding.summary :editable="false" />
                @endif
            </div>
        </main>

        <footer class="onboarding-language-footer position-fixed bottom-0 end-0 p-3">
            <form
                action="{{ route('locale.update') }}"
                method="POST"
                class="onboarding-language d-flex align-items-center gap-2 rounded-2 px-2 py-1"
                @submit.prevent="switchLocale($event)"
                :inert="busy || uncertainCreate"
            >
                @csrf
                <label for="onboarding-locale" class="small fg-2 mb-0">
                    {{ __('common.language') }}
                </label>
                <select
                    id="onboarding-locale"
                    name="locale"
                    @change="$el.form.requestSubmit()"
                    class="form-control rounded-2 w-auto"
                    aria-describedby="onboarding-language-help"
                >
                    @foreach (config('app.supported_locales') as $code => $name)
                        <option value="{{ $code }}" @selected(app()->getLocale() === $code)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                <p id="onboarding-language-help" class="visually-hidden">
                    {{ __('onboarding.language_help') }}
                </p>
            </form>
        </footer>
    </div>
@endsection
