<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Users\UserQuery;
use App\Services\Users\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public const ROLES = ['customer', 'admin'];

    public function __construct(
        private UserQuery $users,
        private UserManagementService $userService
    ) {}

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

        return view('admin.users.index', [
            'users' => $this->users->forAdmin($keyword, $role),
            'keyword' => $keyword,
            'role' => $role,
            'counts' => $this->users->roleCounts(),
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

        $result = $this->userService->updateRole($request->user(), $user, $validated['role']);

        return back()->with($result['type'], $result['message']);
    }
}
