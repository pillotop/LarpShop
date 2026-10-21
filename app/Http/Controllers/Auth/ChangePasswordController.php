<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    // TODO: invalidate other session, like the vendor account or something idk
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->forceFill([
            'password' => Hash::make($validated['password'])
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
