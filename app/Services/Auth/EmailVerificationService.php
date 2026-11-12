<?php

namespace App\Services\Auth;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public const CODE_TTL_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public function isRequired(): bool
    {
        return (bool) config('auth.require_email_verification', true);
    }

    /**
     * Generate a fresh 6-digit code, store only its hash and email it to the user.
     * Returns false when the resend cooldown has not elapsed yet.
     */
    public function send(User $user, bool $respectCooldown = true): bool
    {
        $existing = DB::table('email_verification_codes')->where('user_id', $user->getKey())->first();

        if (
            $respectCooldown && $existing
            && now()->lessThan(Carbon::parse($existing->last_sent_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS))
        ) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_codes')->updateOrInsert(
            ['user_id' => $user->getKey()],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'last_sent_at' => now(),
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ],
        );

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code, self::CODE_TTL_MINUTES));

        return true;
    }

    /**
     * Check a submitted code. Marks the user verified on success.
     */
    public function verify(User $user, string $code): bool
    {
        $record = DB::table('email_verification_codes')->where('user_id', $user->getKey())->first();

        if (! $record || now()->greaterThan($record->expires_at) || $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $record->code_hash)) {
            DB::table('email_verification_codes')->where('user_id', $user->getKey())->increment('attempts');

            return false;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        DB::table('email_verification_codes')->where('user_id', $user->getKey())->delete();

        return true;
    }
}
