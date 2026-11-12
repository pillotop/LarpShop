<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request, GoogleOAuthService $google): RedirectResponse
    {
        if (! $google->isConfigured()) {
            return redirect()->route('login')
                ->withErrors(['google' => __('auth.google.not_configured')]);
        }

        $previous = url()->previous();
        if (
            $request->session()->missing('url.intended')
            && is_string($previous)
            && str_starts_with($previous, url('/'))
        ) {
            $request->session()->put('url.intended', $previous);
        }

        $state = Str::random(40);
        $request->session()->put('oauth_google_state', $state);

        return redirect()->away($google->authorizationUrl($state));
    }

    public function callback(Request $request, GoogleOAuthService $google): RedirectResponse
    {
        $expectedState = $request->session()->pull('oauth_google_state');
        $providedState = (string) $request->query('state', '');

        if (
            ! is_string($expectedState)
            || $expectedState === ''
            || ! hash_equals($expectedState, $providedState)
        ) {
            return redirect()->route('login')
                ->withErrors(['google' => __('auth.google.invalid_state')]);
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('login')
                ->withErrors(['google' => __('auth.google.cancelled')]);
        }

        try {
            $profile = $google->fetchUserProfile((string) $request->query('code'));
            $user = $google->resolveOrCreateUser($profile);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')
                ->withErrors(['google' => __('auth.google.failed')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
