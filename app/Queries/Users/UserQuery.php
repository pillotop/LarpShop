<?php

namespace App\Queries\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserQuery
{
    /**
     * UC-39: Paginated user list for the admin console with store/order counts and filters.
     */
    public function forAdmin(?string $keyword = null, string $role = 'all', int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->withCount(['vendors', 'orders'])->orderBy('user_id');

        if ($keyword !== null && $keyword !== '') {
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

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * UC-39: Summary counts for user filter tabs.
     *
     * @return array{all: int, customer: int, admin: int}
     */
    public function roleCounts(): array
    {
        return [
            'all' => User::count(),
            'customer' => User::where('role', 'customer')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];
    }
}
