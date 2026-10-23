<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public const ROLES = ['customer', 'admin'];

    /**
     * UC-39: List users with search, role filter, and store/order counters.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['all', ...self::ROLES])],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $role = $validated['role'] ?? 'all';

        $query = User::query()->withCount(['vendors', 'orders'])->orderBy('user_id');

        if ($keyword !== '') {
            $escaped = addcslashes($keyword, '\\%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('username', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%")
                    ->orWhere('phone_number', 'like', "%{$escaped}%");
            });
        }

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        $counts = [
            'all' => User::count(),
            'customer' => User::where('role', 'customer')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'keyword' => $keyword,
            'role' => $role,
            'counts' => $counts,
            'roles' => self::ROLES,
        ]);
    }

    /**
     * UC-39: Change a user's account role (customer or admin).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(self::ROLES)],
        ]);

        $newRole = $validated['role'];

        if ($user->role === $newRole) {
            return back()->with('warning', "User '{$user->username}' already has the '{$newRole}' role.");
        }

        if ($user->role === 'admin' && $newRole !== 'admin') {
            if ($user->user_id === $request->user()->user_id) {
                return back()->with('warning', 'You cannot remove your own administrator role.');
            }

            // Lock admin rows so two concurrent demotions cannot remove the last admin.
            $demoted = DB::transaction(function () use ($user, $newRole) {
                $adminCount = User::where('role', 'admin')->lockForUpdate()->count();

                if ($adminCount <= 1) {
                    return false;
                }

                $user->role = $newRole;
                $user->save();

                return true;
            });

            if (! $demoted) {
                return back()->with('warning', 'The last administrator account cannot be demoted.');
            }
        } else {
            $user->role = $newRole;
            $user->save();
        }

        return back()->with('success', "User '{$user->username}' is now a(n) {$newRole}.");
    }
}
