@extends('layouts.business')

@section('title', __('messages.all_users'))

@section('pagewisestyle')
@endsection

@section('pagewisescript')
@endsection

@section('customjs')
    <script>
        let selectedBusinessUserIds = new Set();

        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }

        function updateBusinessBulkUI() {
            const count = selectedBusinessUserIds.size;
            $('#selectedBusinessCount').text(count);
            $('#bulkRemoveCountText').text(count);

            if (count > 0) {
                $('#businessBulkActionsBar').attr('style', 'display: flex !important;');
            } else {
                $('#businessBulkActionsBar').attr('style', 'display: none !important;');
            }

            const total = $('.business-user-checkbox:not(:disabled)').length;
            const checked = $('.business-user-checkbox:checked').length;
            if (total > 0 && checked === total) {
                $('#selectAllBusinessUsers').prop('checked', true).prop('indeterminate', false);
            } else if (checked > 0) {
                $('#selectAllBusinessUsers').prop('checked', false).prop('indeterminate', true);
            } else {
                $('#selectAllBusinessUsers').prop('checked', false).prop('indeterminate', false);
            }
        }

        function restoreBusinessCheckboxStates() {
            $('.business-user-checkbox').each(function() {
                if (selectedBusinessUserIds.has(this.value)) {
                    this.checked = true;
                } else {
                    this.checked = false;
                }
            });
            updateBusinessBulkUI();
        }

        function clearAllBusinessSelections() {
            selectedBusinessUserIds.clear();
            $('.business-user-checkbox').prop('checked', false);
            $('#selectAllBusinessUsers').prop('checked', false).prop('indeterminate', false);
            updateBusinessBulkUI();
        }

        function confirmBulkRemove() {
            if (selectedBusinessUserIds.size === 0) return;
            $('#bulkRemoveModal').modal('show');
        }

        function submitBulkRemove() {
            if (selectedBusinessUserIds.size === 0) return;
            $('#businessBulkActionInput').val('remove');
            $('#businessBulkUserIdsInput').val(JSON.stringify(Array.from(selectedBusinessUserIds)));
            $('#businessBulkActionForm').submit();
        }

        $(document).ready(function() {
            $(document).on('change', '#selectAllBusinessUsers', function() {
                const isChecked = this.checked;
                $('.business-user-checkbox:not(:disabled)').each(function() {
                    this.checked = isChecked;
                    if (isChecked) {
                        selectedBusinessUserIds.add(this.value);
                    } else {
                        selectedBusinessUserIds.delete(this.value);
                    }
                });
                updateBusinessBulkUI();
            });

            $(document).on('change', '.business-user-checkbox', function() {
                if (this.checked) {
                    selectedBusinessUserIds.add(this.value);
                } else {
                    selectedBusinessUserIds.delete(this.value);
                }
                updateBusinessBulkUI();
            });

            const table = document.querySelector('.datatable');
            if (table) {
                const observer = new MutationObserver(function() {
                    restoreBusinessCheckboxStates();
                });
                observer.observe(table, { childList: true, subtree: true });
            }
        });
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.all_users') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.users') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.registered_users') }}
                            <div style="float: right;">
                                <a href="{{ route('business.users.pending') }}" class="btn btn-warning">
                                    <i class="bi bi-hourglass-split"></i> {{ __('messages.pending_requests') }}
                                    @php
                                        $pendingCount = $users->where('status', 'pending')->count() + $users->where('status', 2)->count();
                                    @endphp
                                    @if ($pendingCount > 0)
                                        <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                                    @endif
                                </a>
                            </div>
                        </h5>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('messages.your_business_code') }}: <strong>{{ $business->business_code }}</strong>
                            - {{ __('messages.your_business_code_share') }}
                        </div>

                        <!-- Bulk Actions Bar -->
                        <div id="businessBulkActionsBar" class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between p-2 mb-3 shadow-sm rounded" style="display: none !important;">
                            <div class="d-flex align-items-center me-3 my-1">
                                <i class="bi bi-check2-square text-primary fs-5 me-2"></i>
                                <span class="fw-bold me-2"><span id="selectedBusinessCount">0</span> {{ __('messages.selected') }}</span>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="clearAllBusinessSelections()">
                                    ({{ __('messages.clear_selection') }})
                                </button>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap my-1">
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmBulkRemove()">
                                    <i class="bi bi-trash-fill"></i> {{ __('messages.remove_selected') }}
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                @if ($users->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th data-sortable="false" style="width: 40px; text-align: center;">
                                                        <input type="checkbox" id="selectAllBusinessUsers" class="form-check-input" title="{{ __('messages.select_all') }}">
                                                    </th>
                                                    <th>#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.joined') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $i = 1; @endphp
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <td style="text-align: center;">
                                                            <input type="checkbox" class="form-check-input business-user-checkbox" value="{{ $user->id }}">
                                                        </td>
                                                        <td>{{ $i++ }}</td>
                                                        <td><strong>{{ $user->name . ($user->surname ? ' ' . $user->surname : '') }}</strong></td>
                                                        <td>{{ $user->email }}</td>
                                                        <td>
                                                            @if (in_array($user->status, [1, '1', 'active'], false))
                                                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                                                            @elseif(in_array($user->status, [2, '2', 'pending'], false))
                                                                <span class="badge bg-warning text-dark">{{ __('messages.pending') }}</span>
                                                            @else
                                                                <span class="badge bg-danger">{{ __('messages.rejected') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</td>
                                                        <td>
                                                            @if (in_array($user->status, [2, '2', 'pending'], false))
                                                                <a href="{{ route('business.users.approve', $user->id) }}"
                                                                    style="font-size: 20px; color:#2eca6a !important;"
                                                                    class="text-success me-2"
                                                                    title="{{ __('messages.approve') }}">
                                                                    <i class="bi bi-check-circle-fill"></i>
                                                                </a>
                                                                <a href="{{ route('business.users.reject', $user->id) }}"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    class="text-danger"
                                                                    title="{{ __('messages.reject') }}">
                                                                    <i class="bi bi-x-circle-fill"></i>
                                                                </a>
                                                            @else
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('business.users.remove', $user->id) }}')"
                                                                    class="text-danger"
                                                                    title="{{ __('messages.remove_from_business') }}">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> {{ __('messages.no_records_found') ?? 'No users found.' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Confirm Remove Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_remove') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_remove_user') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <a href="#" id="confirm_url" class="btn btn-danger">{{ __('messages.remove') }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Remove Confirmation Modal -->
    <div class="modal fade" id="bulkRemoveModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('messages.confirm_remove') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_bulk_remove') }}
                    <div class="mt-2 text-muted small">
                        <strong><span id="bulkRemoveCountText">0</span></strong> {{ __('messages.selected') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" class="btn btn-danger" onclick="submitBulkRemove()"><i class="bi bi-trash-fill me-1"></i> {{ __('messages.remove') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Bulk Action Form -->
    <form id="businessBulkActionForm" method="POST" action="{{ route('business.users.bulkaction') }}" style="display:none;">
        @csrf
        <input type="hidden" name="action" id="businessBulkActionInput" value="">
        <input type="hidden" name="user_ids" id="businessBulkUserIdsInput" value="">
    </form>
@endsection
