<table class="table table-borderless" id="latestUsersTable">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">{{ __('messages.name') }}</th>
            <th scope="col">{{ __('messages.email') }}</th>
            <th scope="col">{{ __('messages.user_type') }}</th>
            <th scope="col">{{ __('messages.phone_number') }}</th>
            <th scope="col">{{ __('messages.joined_date') }}</th>
            <th scope="col">{{ __('messages.status') }}</th>
            <th scope="col">{{ __('messages.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($latest_users as $user)
            <tr>
                <th scope="row">
                    <a href="{{ route('updateuser', ['id' => $user->id]) }}">#{{ $user->id }}</a>
                </th>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2 d-flex align-items-center justify-content-center rounded-circle bg-light text-primary fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="fw-semibold text-dark">{{ $user->name . ' ' . $user->surname }}</span>
                    </div>
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    @if ($user->user_type == 1 || strtolower((string)$user->user_type) === 'admin')
                        <span class="badge bg-danger">{{ __('messages.admin') }}</span>
                    @elseif (empty($user->business_id))
                        <span class="badge bg-secondary">{{ __('messages.individual') }}</span>
                    @else
                        <span class="badge bg-info text-dark">
                            {{ __('messages.company') }}: {{ $user->business ? $user->business->name : __('messages.na') }}
                        </span>
                    @endif
                </td>
                <td>{{ $user->phone_no ?: __('messages.na') }}</td>
                <td>
                    <span title="{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '' }}">
                        {{ $user->created_at ? $user->created_at->format('M d, Y') : __('messages.na') }}
                    </span>
                    @if($user->created_at)
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            {{ $user->created_at->diffForHumans() }}
                        </small>
                    @endif
                </td>
                <td>
                    @if (in_array($user->status, [1, '1', 'active'], false))
                        <span class="badge bg-success">{{ __('messages.active') }}</span>
                    @else
                        <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('updateuser', ['id' => $user->id]) }}" style="font-size: 18px; color:#00ACEF !important" class="text-primary me-2" title="{{ __('messages.edit') }}">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-people fs-3 d-block mb-1"></i>
                    {{ __('messages.no_users_joined_in_period') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

