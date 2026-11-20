<table class="table table-borderless" id="latestUsersTable">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">{{ __('messages.name') }}</th>
            <th scope="col">{{ __('messages.email') }}</th>
            <th scope="col">{{ __('messages.user_type') }}</th>
            <th scope="col">{{ __('messages.associated_bank_admin') ?? 'Bank / Business Admin' }}</th>
            <th scope="col">{{ __('messages.phone_number') }}</th>
            <th scope="col">{{ __('messages.joined_date') }}</th>
            <th scope="col">{{ __('messages.status') }}</th>
            <th scope="col">{{ __('messages.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($latest_users as $user)
            @php
                $userEmail = strtolower(trim($user->email ?? ''));
                $administeredBiz = isset($businesses_by_email) 
                    ? $businesses_by_email->get($userEmail) 
                    : $user->administered_business;
                $biz = $user->business ?? $administeredBiz;
                $isBankAdmin = ($administeredBiz !== null) || ($biz && strtolower(trim($biz->email ?? '')) === $userEmail);
            @endphp
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
                    @elseif ($isBankAdmin)
                        <div class="d-flex flex-column align-items-start gap-1">
                            <span class="badge" style="background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%); color: #fff;">
                                <i class="bi bi-shield-shaded me-1"></i>{{ __('messages.bank_admin') ?? 'Bank Admin' }}
                            </span>
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;" title="{{ __('messages.also_regular_user') ?? '+ Regular User Profile' }}">
                                <i class="bi bi-person-check me-1 text-primary"></i>{{ __('messages.also_regular_user') ?? '+ User' }}
                            </span>
                        </div>
                    @elseif ($biz)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-person-workspace me-1"></i>{{ __('messages.bank_staff') ?? 'Bank User' }}
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border">{{ __('messages.individual') }}</span>
                    @endif
                </td>
                <td>
                    @if ($biz)
                        <div class="d-flex align-items-center gap-2">
                            @if ($biz->logo && Storage::exists('public/business_logos/' . $biz->logo))
                                <img src="{{ url('storage/business_logos/' . $biz->logo) }}"
                                    alt="{{ $biz->name }}" class="rounded shadow-sm border flex-shrink-0"
                                    style="width: 28px; height: 28px; object-fit: cover;">
                            @else
                                <div class="rounded d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0"
                                    style="width: 28px; height: 28px; font-size: 12px; {{ $isBankAdmin ? 'background: linear-gradient(135deg, #4338ca, #6366f1);' : 'background: #0d6efd;' }}">
                                    <i class="bi {{ $isBankAdmin ? 'bi-shield-check' : 'bi-bank2' }}"></i>
                                </div>
                            @endif
                            <div style="line-height: 1.2;">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <a href="{{ route('updatebusiness', ['id' => $biz->id]) }}" 
                                       class="fw-bold text-dark text-decoration-none small hover-primary"
                                       title="{{ __('messages.edit') }}: {{ $biz->name }}">
                                        {{ $biz->name }}
                                    </a>
                                    @if (!empty($biz->business_code))
                                        <span class="badge bg-light text-primary border font-monospace px-1 py-0" style="font-size: 0.65rem;">
                                            {{ $biz->business_code }}
                                        </span>
                                    @endif
                                    @if ($isBankAdmin)
                                        <span class="badge bg-indigo-subtle text-indigo border" style="background:#eef2ff; color:#4338ca; border-color:#c7d2fe; font-size:0.65rem;">
                                            <i class="bi bi-star-fill text-warning me-1"></i>{{ __('messages.primary_bank_admin') ?? 'Bank Admin' }}
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($biz->email) && !$isBankAdmin)
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                                        <i class="bi bi-person-badge text-primary me-1"></i>{{ $biz->email }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    @elseif ($user->user_type == 1 || strtolower((string)$user->user_type) === 'admin')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            <i class="bi bi-shield-check me-1"></i>Central Super Admin
                        </span>
                    @else
                        <span class="badge bg-light text-muted border px-2 py-0" style="font-size: 0.75rem;">
                            <i class="bi bi-dash-circle me-1"></i>{{ __('messages.no_bank_associated') ?? 'Individual' }}
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
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="bi bi-people fs-3 d-block mb-1"></i>
                    {{ __('messages.no_users_joined_in_period') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

