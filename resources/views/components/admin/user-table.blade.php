@props([
    'users',
    'roles' => ['customer', 'admin'],
])

@if ($users->isEmpty())
    <div class="text-center py-5 fg-3">
        <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
        No users match your filters.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>User</th>
                    <th>Phone</th>
                    <th class="text-center">Stores</th>
                    <th class="text-center">Orders</th>
                    <th>Joined</th>
                    <th style="min-width: 220px;">Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="fg-3 fw-bold">#{{ $user->user_id }}</td>
                        <td>
                            <div class="fw-semibold fg-body">{{ $user->username }}</div>
                            <small class="fg-3">{{ $user->email }}</small>
                        </td>
                        <td>{{ $user->phone_number ?: '—' }}</td>
                        <td class="text-center">{{ $user->vendors_count }}</td>
                        <td class="text-center">{{ $user->orders_count }}</td>
                        <td class="small fg-3">{{ $user->created_at?->format('d/m/Y') }}</td>
                        <td>
                            <form action="{{ route('admin.users.update-role', $user->user_id) }}" method="POST" class="d-flex gap-2 m-0">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="form-control form-control-sm">
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption }}" @selected($user->role === $roleOption)>{{ ucfirst($roleOption) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-solid theme-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small fg-3">
            Showing <strong>{{ $users->firstItem() }}</strong> to <strong>{{ $users->lastItem() }}</strong> of <strong>{{ $users->total() }}</strong> users
        </div>
        <div>{{ $users->links('shared.pagination') }}</div>
    </div>
@endif
