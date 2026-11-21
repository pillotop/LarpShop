<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileManagementService
{
    public function update(User $user, array $data, ?UploadedFile $image = null): User
    {
        $newPath = null;
        $previousPath = null;
        $imageChanged = $image !== null || ! empty($data['remove_profile_image']);
        $directory = 'users/profiles/'.$user->getKey();

        try {
            $user = DB::transaction(function () use ($user, $data, $image, $imageChanged, $directory, &$newPath, &$previousPath) {
                $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $previousPath = $user->profile_path;

                if ($image !== null) {
                    $newPath = $image->store($directory, 'public');
                    if ($newPath === false) {
                        throw ValidationException::withMessages([
                            'profile_image' => __('profile.photo.upload_failed'),
                        ]);
                    }
                }

                $user->fill(Arr::only($data, ['username', 'email', 'phone_number', 'date_of_birth', 'gender']));
                if ($imageChanged) {
                    $user->profile_path = $newPath;
                }
                $user->save();

                return $user;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        // Only remove this account's managed upload, never a shared or default image.
        if ($imageChanged && $previousPath && str_starts_with($previousPath, $directory.'/')) {
            Storage::disk('public')->delete($previousPath);
        }

        return $user;
    }
}
