@extends('layouts.app')
@section('pagewisestyle')
<style>
    .bank-badge-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 10px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        max-width: 340px;
    }
    .bank-badge-card:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .bank-badge-card.is-admin {
        background: #f5f3ff;
        border-color: #ddd6fe;
    }
    .bank-badge-card.is-admin:hover {
        background: #ede9fe;
        border-color: #c4b5fd;
    }
    .badge-bank-admin {
        background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
        color: #ffffff !important;
        font-weight: 600;
        letter-spacing: 0.02em;
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
        border: none;
    }
    .badge-bank-user {
        background-color: #e0f2fe;
        color: #0369a1 !important;
        border: 1px solid #bae6fd;
        font-weight: 500;
    }
    .bank-admin-tag {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 2px 6px;
        border-radius: 4px;
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }
    .bank-member-tag {
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 2px 6px;
        border-radius: 4px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .hover-primary:hover {
        color: #0d6efd !important;
    }
</style>
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        let selectedUserIds = new Set();

        function update_status(el) {
            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('changeuserstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('{{ __('messages.user_status_updated') }}', 'success', '{{ __('messages.success') }}');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', '{{ __('messages.danger') }}');
                }
            });
        }

        function updateBulkUI() {
            const count = selectedUserIds.size;
            $('#selectedCount').text(count);
            $('#bulkDeleteCountText').text(count);
            $('#bulkCompanyCountText').text(count);

            if (count > 0) {
                $('#bulkActionsBar').attr('style', 'display: flex !important;');
            } else {
                $('#bulkActionsBar').attr('style', 'display: none !important;');
            }

            const totalCheckboxes = $('.user-checkbox:not(:disabled)').length;
            const checkedCheckboxes = $('.user-checkbox:checked').length;
            if (totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes) {
                $('#selectAllUsers').prop('checked', true).prop('indeterminate', false);
            } else if (checkedCheckboxes > 0) {
                $('#selectAllUsers').prop('checked', false).prop('indeterminate', true);
            } else {
                $('#selectAllUsers').prop('checked', false).prop('indeterminate', false);
            }
        }

        function restoreCheckboxStates() {
            $('.user-checkbox').each(function() {
                if (selectedUserIds.has(this.value)) {
                    this.checked = true;
                } else {
                    this.checked = false;
                }
            });
            updateBulkUI();
        }

        function clearAllSelections() {
            selectedUserIds.clear();
            $('.user-checkbox').prop('checked', false);
            $('#selectAllUsers').prop('checked', false).prop('indeterminate', false);
            updateBulkUI();
        }

        function confirmBulkDelete() {
            if (selectedUserIds.size === 0) return;
            $('#bulkDeleteModal').modal('show');
        }

        function submitBulkDelete() {
            if (selectedUserIds.size === 0) return;
            $('#bulkActionInput').val('delete');
            $('#bulkUserIdsInput').val(JSON.stringify(Array.from(selectedUserIds)));
            $('#bulkActionForm').submit();
        }

        function openBulkCompanyModal() {
            if (selectedUserIds.size === 0) return;
            $('#bulkCompanyModal').modal('show');
        }

        function submitBulkCompany() {
            if (selectedUserIds.size === 0) return;
            $('#bulkActionInput').val('assign_company');
            $('#bulkBusinessIdInput').val($('#bulkCompanySelect').val());
            $('#bulkUserIdsInput').val(JSON.stringify(Array.from(selectedUserIds)));
            $('#bulkActionForm').submit();
        }

        function submitBulkStatus(status) {
            if (selectedUserIds.size === 0) return;
            $('#bulkActionInput').val('change_status');
            $('#bulkStatusInput').val(status);
            $('#bulkUserIdsInput').val(JSON.stringify(Array.from(selectedUserIds)));
            $('#bulkActionForm').submit();
        }

        $(document).ready(function() {
            $(document).on('change', '#selectAllUsers', function() {
                const isChecked = this.checked;
                $('.user-checkbox:not(:disabled)').each(function() {
                    this.checked = isChecked;
                    if (isChecked) {
                        selectedUserIds.add(this.value);
                    } else {
                        selectedUserIds.delete(this.value);
                    }
                });
                updateBulkUI();
            });

            $(document).on('change', '.user-checkbox', function() {
                if (this.checked) {
                    selectedUserIds.add(this.value);
                } else {
                    selectedUserIds.delete(this.value);
                }
                updateBulkUI();
            });

            const table = document.querySelector('.datatable');
            if (table) {
                const observer = new MutationObserver(function() {
                    restoreCheckboxStates();
                });
                observer.observe(table, { childList: true, subtree: true });
            }
        });
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.manage_users') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.manage_users') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- @include('partials.messages') -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title d-flex justify-content-between align-items-center">
                                <span>{{ __('messages.manage_users') }}</span>
                                <form method="GET" action="{{ route('manageusers') }}" class="d-flex align-items-center"
                                    style="gap: 10px;">
                                    <label for="companyFilter" class="form-label mb-0 fw-bold">{{ __('messages.filter_by') }}</label>
                                    <select name="company" id="companyFilter" class="form-select w-auto"
                                        onchange="this.form.submit()">
                                        <option value="">{{ __('messages.all_users') }}</option>
                                        <option value="admin"
                                            {{ isset($current_filter) && $current_filter == 'admin' ? 'selected' : '' }}>
                                            {{ __('messages.admin') }}</option>
                                        <option value="bank_admins"
                                            {{ isset($current_filter) && $current_filter == 'bank_admins' ? 'selected' : '' }}>
                                            {{ __('messages.bank_admins') ?? 'Bank Admins' }}</option>
                                        <option value="individual"
                                            {{ isset($current_filter) && $current_filter == 'individual' ? 'selected' : '' }}>
                                            {{ __('messages.individual_users') }}</option>
                                        @foreach ($businesses as $business)
                                            <option value="{{ $business->id }}"
                                                {{ isset($current_filter) && $current_filter == $business->id ? 'selected' : '' }}>
                                                {{ __('messages.bank_or_business') ?? 'Bank' }}: {{ $business->name }}@if(!empty($business->business_code)) ({{ $business->business_code }})@endif</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('exportusers', ['company' => request('company')]) }}"
                                        class="btn btn-primary ms-2">
                                        <i class="bi bi-file-earmark-excel"></i> {{ __('messages.export') }}
                                    </a>
                                    <a href="{{ route('createuser') }}" class="btn btn-primary"><i
                                                class="bi bi-person-plus"></i> {{ __('messages.add_user') }}</a>
                                </form>
                            </h5>
                            <!-- Bulk Actions Bar -->
                            <div id="bulkActionsBar" class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between p-2 mb-3 shadow-sm rounded" style="display: none !important;">
                                <div class="d-flex align-items-center me-3 my-1">
                                    <i class="bi bi-check2-square text-primary fs-5 me-2"></i>
                                    <span class="fw-bold me-2"><span id="selectedCount">0</span> {{ __('messages.selected') }}</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="clearAllSelections()">
                                        ({{ __('messages.clear_selection') }})
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap my-1">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openBulkCompanyModal()">
                                        <i class="bi bi-bank2"></i> {{ __('messages.assign_company') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="submitBulkStatus(1)">
                                        <i class="bi bi-check-circle"></i> {{ __('messages.activate') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="submitBulkStatus(0)">
                                        <i class="bi bi-slash-circle"></i> {{ __('messages.deactivate') }}
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmBulkDelete()">
                                        <i class="bi bi-trash-fill"></i> {{ __('messages.delete_selected') }}
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable align-middle table-hover">
                                            <thead>
                                                <tr> 
                                                    <th data-sortable="false" style="width: 40px; text-align: center;">
                                                        <input type="checkbox" id="selectAllUsers" class="form-check-input" title="{{ __('messages.select_all') }}">
                                                    </th>
                                                    <th style="width: 45px;">#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th style="min-width: 120px;">{{ __('messages.user_type') ?? 'User Type' }}</th>
                                                    <th style="min-width: 240px;">{{ __('messages.associated_bank_admin') ?? 'Bank / Business Admin' }}</th>
                                                    <th style="width: 90px; text-align: center;">{{ __('messages.status') }}</th>
                                                    <th style="width: 100px; text-align: center;">{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($users as $val)
                                                    @php
                                                        $userEmail = strtolower(trim($val->email ?? ''));
                                                        $administeredBiz = isset($businesses_by_email) 
                                                            ? $businesses_by_email->get($userEmail) 
                                                            : $val->administered_business;
                                                        $biz = $val->business ?? $administeredBiz;
                                                        $isBankAdmin = ($administeredBiz !== null) || ($biz && strtolower(trim($biz->email ?? '')) === $userEmail);
                                                    @endphp
                                                    <tr>
                                                        <td style="text-align: center;">
                                                            @if (Auth::id() != $val->id)
                                                                <input class="form-check-input user-checkbox" type="checkbox" value="{{ $val->id }}">
                                                            @else
                                                                <span class="text-muted" title="{{ __('messages.cannot_select_self') }}">-</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $i }}</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm me-2 d-flex align-items-center justify-content-center rounded-circle bg-light text-primary fw-bold border" 
                                                                     style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                                                                    {{ strtoupper(substr($val->name ?? 'U', 0, 1)) }}
                                                                </div>
                                                                <div>
                                                                    <span class="fw-semibold text-dark">{{ $val->name . ' ' . $val->surname }}</span>
                                                                    @if(!empty($val->job_title) || !empty($val->department))
                                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                                            {{ implode(' • ', array_filter([$val->job_title, $val->department])) }}
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="text-secondary small">
                                                                <i class="bi bi-envelope me-1 text-muted"></i>{{ $val->email }}
                                                            </span>
                                                            @if(!empty($val->phone_no))
                                                                <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                                    <i class="bi bi-telephone me-1"></i>{{ $val->phone_no }}
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($val->user_type == 1 || strtolower((string)$val->user_type) === 'admin')
                                                                <span class="badge bg-danger text-white px-2 py-1 shadow-sm">
                                                                    <i class="bi bi-shield-lock-fill me-1"></i>{{ __('messages.admin') }}
                                                                </span>
                                                            @elseif ($isBankAdmin)
                                                                <div class="d-flex flex-column align-items-start gap-1">
                                                                    <span class="badge badge-bank-admin px-2 py-1">
                                                                        <i class="bi bi-shield-shaded me-1"></i>{{ __('messages.bank_admin') ?? 'Bank Admin' }}
                                                                    </span>
                                                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;" title="{{ __('messages.also_regular_user') ?? '+ Regular User Profile' }}">
                                                                        <i class="bi bi-person-check me-1 text-primary"></i>{{ __('messages.also_regular_user') ?? '+ Regular User Profile' }}
                                                                    </span>
                                                                </div>
                                                            @elseif ($biz)
                                                                <span class="badge badge-bank-user px-2 py-1">
                                                                    <i class="bi bi-person-workspace me-1"></i>{{ __('messages.bank_staff') ?? 'Bank User' }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                                                    <i class="bi bi-person me-1"></i>{{ __('messages.individual') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($biz)
                                                                <div class="bank-badge-card {{ $isBankAdmin ? 'is-admin' : '' }}">
                                                                    @if ($biz->logo && Storage::exists('public/business_logos/' . $biz->logo))
                                                                        <img src="{{ url('storage/business_logos/' . $biz->logo) }}"
                                                                            alt="{{ $biz->name }}" class="rounded shadow-sm border flex-shrink-0"
                                                                            style="width: 32px; height: 32px; object-fit: cover;">
                                                                    @else
                                                                        <div class="rounded d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0"
                                                                            style="width: 32px; height: 32px; font-size: 14px; {{ $isBankAdmin ? 'background: linear-gradient(135deg, #4338ca, #6366f1);' : 'background: #0d6efd;' }}">
                                                                            <i class="bi {{ $isBankAdmin ? 'bi-shield-check' : 'bi-bank2' }}"></i>
                                                                        </div>
                                                                    @endif
                                                                    <div class="d-flex flex-column" style="line-height: 1.25;">
                                                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                            <a href="{{ route('updatebusiness', ['id' => $biz->id]) }}" 
                                                                               class="fw-bold text-dark text-decoration-none hover-primary"
                                                                               title="{{ __('messages.edit') }}: {{ $biz->name }}">
                                                                                {{ $biz->name }}
                                                                            </a>
                                                                            @if (!empty($biz->business_code))
                                                                                <span class="badge bg-light text-primary border border-primary-subtle font-monospace px-1 py-0" 
                                                                                      style="font-size: 0.68rem;" title="Bank Code: {{ $biz->business_code }}">
                                                                                    {{ $biz->business_code }}
                                                                                </span>
                                                                            @endif
                                                                            @if ($isBankAdmin)
                                                                                <span class="bank-admin-tag" title="{{ __('messages.primary_admin_account') ?? 'Primary Bank Admin' }}">
                                                                                    <i class="bi bi-star-fill text-warning me-1"></i>{{ __('messages.primary_bank_admin') ?? 'Bank Admin' }}
                                                                                </span>
                                                                            @else
                                                                                <span class="bank-member-tag" title="Bank Staff Member">
                                                                                    <i class="bi bi-people me-1"></i>{{ __('messages.bank_member') ?? 'Member' }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                        @if (!empty($biz->email))
                                                                            <small class="text-muted mt-1 text-nowrap" style="font-size: 0.75rem;" title="{{ __('messages.bank_admin_email') ?? 'Bank Admin Email' }}">
                                                                                <i class="bi bi-person-badge text-primary me-1"></i>{{ $biz->email }}
                                                                            </small>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @elseif (!empty($val->business_id))
                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                                    <i class="bi bi-exclamation-triangle me-1"></i>Bank ID #{{ $val->business_id }} (Not Found)
                                                                </span>
                                                            @elseif ($val->user_type == 1 || strtolower((string)$val->user_type) === 'admin')
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                                    <i class="bi bi-shield-check me-1"></i>{{ __('messages.central_super_admin') ?? 'Central Super Admin' }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-light text-muted border px-2 py-1">
                                                                    <i class="bi bi-dash-circle me-1"></i>{{ __('messages.no_bank_associated') ?? 'Individual (No Bank)' }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input"
                                                                    onchange="update_status(this)" type="checkbox"
                                                                    {{ in_array($val->status, [1, '1', 'active'], false) ? 'checked' : '' }}
                                                                    value="{{ $val->id }}">
                                                            </div>
                                                        </td>
                                                        <td style="text-align: center; white-space: nowrap;">
                                                            <a href="{{ route('updateuser', ['id' => $val->id]) }}" style="font-size: 18px; color:#00ACEF !important" class="text-primary me-2" title="{{ __('messages.edit') }}">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            @if (Auth::id() != $val->id)
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 18px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deleteuser', ['id' => $val->id]) }}')"
                                                                    class="text-danger" title="{{ __('messages.delete') }}"><i class="bi bi-trash-fill"></i></a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                        {{-- <table>
                                            <tr>
                                                <td>
                                                    <a href="{{route('createuser')}}" class="btn btn-primary">Add New</a>&nbsp;&nbsp;
                                                </td>
                                            </tr>
                                        </table> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('messages.confirm_delete') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_bulk_delete') }}
                    <div class="mt-2 text-muted small">
                        <strong><span id="bulkDeleteCountText">0</span></strong> {{ __('messages.selected') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" class="btn btn-danger" onclick="submitBulkDelete()"><i class="bi bi-trash-fill me-1"></i> {{ __('messages.delete') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Assign Company Modal -->
    <div class="modal fade" id="bulkCompanyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bank2 me-2 text-primary"></i>{{ __('messages.assign_company') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ __('messages.assign_company') }} (<strong><span id="bulkCompanyCountText">0</span></strong> {{ __('messages.selected') }}):</p>
                    <select id="bulkCompanySelect" class="form-select">
                        <option value="">-- {{ __('messages.no_bank_associated') ?? 'Individual Users (No Bank)' }} --</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->name }}@if(!empty($business->business_code)) ({{ $business->business_code }})@endif - {{ $business->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="submitBulkCompany()"><i class="bi bi-check-lg me-1"></i> {{ __('messages.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Bulk Action Form -->
    <form id="bulkActionForm" method="POST" action="{{ route('user.bulkaction') }}" style="display:none;">
        @csrf
        <input type="hidden" name="action" id="bulkActionInput" value="">
        <input type="hidden" name="business_id" id="bulkBusinessIdInput" value="">
        <input type="hidden" name="status" id="bulkStatusInput" value="">
        <input type="hidden" name="user_ids" id="bulkUserIdsInput" value="">
    </form>

    @include('partials.footer')
@endsection
