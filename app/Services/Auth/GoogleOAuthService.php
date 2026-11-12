<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::AUTH_URL.'?'.$query;
    }

    /**
     * Exchange an authorization code for the authenticated Google user profile.
     *
     * @return array{sub: string, email: string, name?: string, email_verified?: bool}
     */
    public function fetchUserProfile(string $code): array
    {
        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ])
            ->throw();

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google OAuth token response did not contain an access token.');
        }

        $profile = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get(self::USERINFO_URL)
            ->throw()
            ->json();

        if (
            ! is_array($profile)
            || empty($profile['sub'])
            || empty($profile['email'])
            || ($profile['email_verified'] ?? false) !== true
        ) {
            throw new RuntimeException('Google account does not have a verified email address.');
        }

        return $profile;
    }

    /**
     * Find an existing user by Google ID or email, or register a verified customer account.
     */
    public function resolveOrCreateUser(array $profile): User
    {
        $googleId = (string) $profile['sub'];
        $email = mb_strtolower(trim((string) $profile['email']));

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $dirty = false;

            if ($user->google_id === null) {
                $user->google_id = $googleId;
                $dirty = true;
            }

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
                $dirty = true;
            }

            if ($dirty) {
                $user->save();
            }

            return $user;
        }

        $baseName = trim((string) ($profile['name'] ?? strstr($email, '@', true) ?: 'user'));
        $username = $this->uniqueUsername($baseName);

        $user = new User;
        $user->username = $username;
        $user->email = $email;
        $user->google_id = $googleId;
        $user->email_verified_at = now();
        $user->password = Str::password(32);
        $user->role = 'customer';
        $user->save();

        return $user;
    }

    private function uniqueUsername(string $baseName): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}_.\- ]/u', '', $baseName) ?: 'user';
        $candidate = mb_substr(trim($clean), 0, 42) ?: 'user';

        if (! User::where('username', $candidate)->exists()) {
            return $candidate;
        }

        for ($i = 1; $i <= 50; $i++) {
            $suffixed = $candidate.'_'.random_int(100, 9999);
            if (! User::where('username', $suffixed)->exists()) {
                return $suffixed;
            }
        }

        return 'user_'.Str::lower(Str::random(8));
    }
}
