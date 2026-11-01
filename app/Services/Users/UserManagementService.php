<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    /**
     * UC-39: Change a user's account role (customer or admin).
     * Prevents self-demotion and protects the last remaining administrator account.
     *
     * @return array{type: 'success'|'warning', message: string}
     */
    public function updateRole(User $actor, User $target, string $newRole): array
    {
        if ($target->role === $newRole) {
            return [
                'type' => 'warning',
                'message' => __('management.messages.role_unchanged', ['name' => $target->username, 'role' => __('profile.role.'.$newRole)]),
            ];
        }

        if ($target->role === 'admin' && $newRole !== 'admin') {
            if ($target->user_id === $actor->user_id) {
                return [
                    'type' => 'warning',
                    'message' => __('management.messages.own_admin'),
                ];
            }

            // Lock admin rows so two concurrent demotions cannot remove the last admin.
            $demoted = DB::transaction(function () use ($target, $newRole) {
                $adminCount = User::where('role', 'admin')->lockForUpdate()->count();

                if ($adminCount <= 1) {
                    return false;
                }

                $target->role = $newRole;
                $target->save();

                return true;
            });

            if (! $demoted) {
                return [
                    'type' => 'warning',
                    'message' => __('management.messages.last_admin'),
                ];
            }
        } else {
            $target->role = $newRole;
            $target->save();
        }

        return [
            'type' => 'success',
            'message' => __('management.messages.role_changed', ['name' => $target->username, 'role' => __('profile.role.'.$newRole)]),
        ];
    }
}
