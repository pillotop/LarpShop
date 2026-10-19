<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('forgot-password');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => [
                'required', 'string', 'confirmed', Password::min(8), 'different:old_password',
            ]
        ]);

        $user = $request->user();

        if (! Hash::check($validated['old_password'], $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'old_password' => 'The current password is incorrect.',
            ]);
        }

        $user->password = $validated['new_password'];
        $user->save();

        Auth::logoutOtherDevices($validated['old_password']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password changed successfully',
                'redirect' => route('home'),
            ]);
        }

        return redirect()->route('home')
            ->with('status', 'Password changed successfully');
    }
}
