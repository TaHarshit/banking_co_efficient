@extends('layouts.business')

@section('title', __('messages.bank_managers') ?? 'Bank Managers')

@section('pagewisestyle')
@endsection

@section('pagewisescript')
@endsection

@section('customjs')
    <script>
        function update_status(el) {
            var status = el.checked ? 1 : 0;
            $.post('{{ route('business.managers.status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data && data.status == 1) {
                    NotifMsg('{{ __('messages.manager_status_updated') ?? 'Manager status updated successfully' }}', 'success', '{{ __('messages.success') }}');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', '{{ __('messages.danger') }}');
                }
            }).fail(function() {
                NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', '{{ __('messages.danger') }}');
            });
        }

        function openEditModal(manager) {
            $('#edit_manager_form').attr('action', '{{ url('business/managers/update') }}/' + manager.id);
            $('#edit_name').val(manager.name);
            $('#edit_email').val(manager.email);
            $('#edit_phone').val(manager.phone || '');
            $('#edit_department').val(manager.department || '');
            $('#edit_status').val(manager.status);
            $('#edit_password').val('');
            $('#editManagerModal').modal('show');
        }

        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.bank_managers') ?? 'Bank Managers' }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.bank_managers') ?? 'Bank Managers' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 pb-2 gap-2">
                            <div>
                                <h5 class="card-title mb-0 p-0">{{ __('messages.bank_managers') ?? 'Bank Managers' }}</h5>
                                <small class="text-muted">{{ __('messages.bank_managers_desc') ?? 'Managers can log into the business portal to review and evaluate assessment results.' }}</small>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addManagerModal">
                                <i class="bi bi-person-plus-fill me-1"></i> {{ __('messages.add_manager') ?? 'Add New Manager' }}
                            </button>
                        </div>

                        <!-- Filter Form -->
                        <form method="GET" action="{{ route('business.managers') }}" class="row g-2 align-items-center mt-2 mb-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="{{ __('messages.search_manager_placeholder') ?? 'Search by name, email, or department...' }}"
                                        value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="department" class="form-select">
                                    <option value="">-- {{ __('messages.all_departments') ?? 'All Departments' }} --</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                                            {{ $dept }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-50">
                                    <i class="bi bi-filter"></i> {{ __('messages.filter') ?? 'Filter' }}
                                </button>
                                <a href="{{ route('business.managers') }}" class="btn btn-outline-secondary w-50">
                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('messages.reset') }}
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.department') }}</th>
                                        <th>{{ __('messages.phone') ?? 'Phone' }}</th>
                                        <th style="width: 100px;">{{ __('messages.status') }}</th>
                                        <th style="width: 120px;">{{ __('messages.date') ?? 'Created At' }}</th>
                                        <th style="width: 120px; text-align: center;">{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($managers as $key => $val)
                                        <tr>
                                            <td>{{ $managers->firstItem() + $key }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="width: 34px; height: 34px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                        {{ strtoupper(substr($val->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <strong>{{ $val->name }}</strong>
                                                        <span class="badge bg-info text-dark d-block" style="font-size: 10px; width: fit-content;">{{ __('messages.bank_manager') ?? 'Manager' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $val->email }}</td>
                                            <td>
                                                @if ($val->department)
                                                    <span class="badge bg-light text-dark border"><i class="bi bi-building me-1"></i>{{ $val->department }}</span>
                                                @else
                                                    <span class="text-muted small"><em>{{ __('messages.all_departments') ?? 'All Departments' }}</em></span>
                                                @endif
                                            </td>
                                            <td>{{ $val->phone ?? __('messages.na') }}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input"
                                                        onchange="update_status(this)" type="checkbox"
                                                        {{ $val->status == 1 ? 'checked' : '' }}
                                                        value="{{ $val->id }}">
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $val->created_at ? $val->created_at->format('M d, Y') : __('messages.na') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <a href="javascript:void(0);"
                                                        onclick="openEditModal({{ json_encode($val) }})"
                                                        style="font-size: 18px; color: #00ACEF !important;"
                                                        title="{{ __('messages.edit') }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                        onclick="showConfirmToast('{{ route('business.managers.delete', ['id' => $val->id]) }}')"
                                                        style="font-size: 18px; color: #EE6C4D !important;"
                                                        title="{{ __('messages.delete') }}">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                                {{ __('messages.no_managers_found') ?? 'No bank managers found.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            {{ $managers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal: Add New Manager -->
    <div class="modal fade" id="addManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('business.managers.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2 text-primary"></i>{{ __('messages.add_manager') ?? 'Add New Bank Manager' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('messages.email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="manager@bank.com">
                            <small class="text-muted">{{ __('messages.manager_login_email_hint') ?? 'This email will be used to log in at the Business portal.' }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('messages.password') ?? 'Password' }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6" placeholder="{{ __('messages.password_min_chars') ?? 'Minimum 6 characters' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.department') }}</label>
                            <input type="text" name="department" list="departmentList" class="form-control" placeholder="e.g. Commercial Lending, Risk Assessment">
                            <datalist id="departmentList">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept }}">
                                @endforeach
                            </datalist>
                            <small class="text-muted">{{ __('messages.department_scoping_hint') ?? 'Optional. Scopes results to this department if set.' }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.phone') ?? 'Phone Number' }}</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 234 567 890">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') ?? 'Cancel' }}</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>{{ __('messages.save') ?? 'Save Manager' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Manager -->
    <div class="modal fade" id="editManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="edit_manager_form" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('messages.edit_manager') ?? 'Edit Bank Manager' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('messages.email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.new_password') ?? 'New Password' }}</label>
                            <input type="password" name="password" id="edit_password" class="form-control" minlength="6" placeholder="{{ __('messages.leave_blank_keep_current') ?? 'Leave blank to keep current password' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.department') }}</label>
                            <input type="text" name="department" id="edit_department" list="departmentListEdit" class="form-control">
                            <datalist id="departmentListEdit">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.phone') ?? 'Phone Number' }}</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.status') }}</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="1">{{ __('messages.active') ?? 'Active' }}</option>
                                <option value="0">{{ __('messages.inactive') ?? 'Inactive' }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') ?? 'Cancel' }}</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>{{ __('messages.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_delete') ?? 'Confirm Delete' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-1 d-block mb-2"></i>
                    <p class="mb-0">{{ __('messages.confirm_delete_manager') ?? 'Are you sure you want to delete this bank manager?' }}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') ?? 'Cancel' }}</button>
                    <a id="confirm_url" href="#" class="btn btn-danger btn-sm">{{ __('messages.delete') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
