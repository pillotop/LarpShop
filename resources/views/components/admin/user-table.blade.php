@props([
    'users',
    'roles' => ['customer', 'admin'],
])

@if ($users->isEmpty())
    <div class="text-center py-5 fg-3">
        <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
        {{ __('management.no_users_match_your_filters') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
                <tr>
                    <th style="width: 60px;">{{ __('management.id_text') }}</th>
                    <th>{{ __('management.user') }}</th>
                    <th>{{ __('management.phone') }}</th>
                    <th class="text-center">{{ __('management.stores') }}</th>
                    <th class="text-center">{{ __('management.orders') }}</th>
                    <th>{{ __('management.joined') }}</th>
                    <th style="min-width: 220px;">{{ __('management.role') }}</th>
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
                                        <option value="{{ $roleOption }}" @selected($user->role === $roleOption)>{{ __('profile.role.'.$roleOption) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-solid theme-primary">{{ __('management.save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small fg-3">
            {{ __('pagination.summary', ['first' => $users->firstItem(), 'last' => $users->lastItem(), 'total' => $users->total()]) }}
        </div>
        <div>{{ $users->links('shared.pagination') }}</div>
    </div>
@endif
