@extends('layouts.admin')

@section('title', 'User Management (UC-39)')
@section('page_title', 'Users & Roles')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-people text-primary me-2"></i>User Management</h1>
            <p class="text-muted small mb-0">Review accounts and assign the customer or administrator role. Store ownership is managed separately under Vendors.</p>
        </div>

        <ul class="nav nav-pills gap-2 mb-3">
            @foreach (['all' => 'All', 'customer' => 'Customers', 'admin' => 'Admins'] as $key => $label)
                <li class="nav-item">
                    <a href="{{ route('admin.users.index', array_filter(['role' => $key, 'q' => $keyword])) }}"
                        class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $role === $key ? 'active' : 'bg-light text-dark' }}">
                        {{ $label }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-4">
            <input type="hidden" name="role" value="{{ $role }}">
            <div class="col-12 col-md-9">
                <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="Search by username, email, or phone">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Search</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        @if ($users->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No users match your filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
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
                                <td class="text-muted fw-bold">#{{ $user->user_id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $user->username }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </td>
                                <td>{{ $user->phone_number ?: '—' }}</td>
                                <td class="text-center">{{ $user->vendors_count }}</td>
                                <td class="text-center">{{ $user->orders_count }}</td>
                                <td class="small text-muted">{{ $user->created_at?->format('d/m/Y') }}</td>
                                <td>
                                    <form action="{{ route('admin.users.update-role', $user->user_id) }}" method="POST" class="d-flex gap-2 m-0">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="form-select form-select-sm">
                                            @foreach ($roles as $roleOption)
                                                <option value="{{ $roleOption }}" @selected($user->role === $roleOption)>{{ ucfirst($roleOption) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing <strong>{{ $users->firstItem() }}</strong> to <strong>{{ $users->lastItem() }}</strong> of <strong>{{ $users->total() }}</strong> users
                </div>
                <div>{{ $users->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
