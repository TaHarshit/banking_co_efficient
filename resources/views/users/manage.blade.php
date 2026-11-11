@extends('layouts.app')
@section('pagewisestyle')
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
                                        <option value="individual"
                                            {{ isset($current_filter) && $current_filter == 'individual' ? 'selected' : '' }}>
                                            {{ __('messages.individual_users') }}</option>
                                        @foreach ($businesses as $business)
                                            <option value="{{ $business->id }}"
                                                {{ isset($current_filter) && $current_filter == $business->id ? 'selected' : '' }}>
                                                {{ __('messages.company') }}: {{ $business->name }}</option>
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
                                        <i class="bi bi-building"></i> {{ __('messages.assign_company') }}
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
                                        <table class="table datatable">
                                            <thead>
                                                <tr> 
                                                    <th data-sortable="false" style="width: 40px; text-align: center;">
                                                        <input type="checkbox" id="selectAllUsers" class="form-check-input" title="{{ __('messages.select_all') }}">
                                                    </th>
                                                    <th>#</th>
                                                    <th>{{ __('messages.user_type') }}</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($users as $val)
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
                                                            @if ($val->user_type == 1 || strtolower((string)$val->user_type) === 'admin')
                                                                <span class="badge bg-danger">{{ __('messages.admin') }}</span>
                                                            @elseif (empty($val->business_id))
                                                                <span class="badge bg-secondary">{{ __('messages.individual') }}</span>
                                                            @else
                                                                <span class="badge bg-info text-dark">{{ __('messages.company') }}:
                                                                    {{ $val->business ? $val->business->name : __('messages.na') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $val->name . ' ' . $val->surname }}</td>
                                                        <td>{{ $val->email }}</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input"
                                                                    onchange="update_status(this)" type="checkbox"
                                                                    {{ in_array($val->status, [1, '1', 'active'], false) ? 'checked' : '' }}
                                                                    value="{{ $val->id }}">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{route('updateuser',['id'=>$val->id])}}" style="font-size: 20px; color:#00ACEF !important" class="text-primary">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            @if (Auth::id() != $val->id)
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deleteuser', ['id' => $val->id]) }}')"
                                                                    class="text-danger"><i class="bi bi-trash-fill"></i></a>
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
                    <h5 class="modal-title"><i class="bi bi-building me-2"></i>{{ __('messages.assign_company') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ __('messages.assign_company') }} (<strong><span id="bulkCompanyCountText">0</span></strong> {{ __('messages.selected') }}):</p>
                    <select id="bulkCompanySelect" class="form-select">
                        <option value="">-- {{ __('messages.individual_users') }} --</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business->id }}">{{ __('messages.company') }}: {{ $business->name }}</option>
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
