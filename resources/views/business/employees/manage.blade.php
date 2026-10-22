@extends('layouts.business')

@section('title', __('messages.employees'))

@section('pagewisestyle')
@endsection

@section('pagewisescript')
@endsection

@section('customjs')
    <script>
        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.employees') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.employees') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                @php
                    $usedQ = $business->getUsedQuota();
                    $totQ = (int)($business->user_quota ?? 0);
                    $remQ = $business->getRemainingQuota();
                    $isSubActive = $business->isSubscriptionActive();
                @endphp

                @if(!$isSubActive)
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>
                        {{ __('messages.business_sub_inactive_notice') }}
                    </div>
                @elseif($remQ <= 0)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ __('messages.quota_exceeded_notice') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.employee_list') }}
                            <span class="badge {{ $remQ > 0 ? 'bg-primary' : 'bg-danger' }} ms-2" style="font-size: 13px; font-weight: 500;">
                                <i class="bi bi-people me-1"></i>{{ __('messages.seats') }}: {{ $usedQ }} / {{ $totQ }} {{ __('messages.used') }} ({{ $remQ }} {{ __('messages.available') }})
                            </span>
                            <div style="float: right;">
                                <a href="{{ route('business.employees.import') }}" class="btn btn-success me-2">
                                    <i class="bi bi-file-earmark-excel"></i> {{ __('messages.import_excel') }}
                                </a>
                                <a href="{{ route('business.employees.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus"></i> {{ __('messages.add_employee') }}
                                </a>
                            </div>
                        </h5>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('messages.employees_register_hint') }}
                            <strong>{{ $business->business_code }}</strong>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                @if ($employees->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.department') }}</th>
                                                    <th>{{ __('messages.phone_number') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $i = 1; @endphp
                                                @foreach ($employees as $emp)
                                                    <tr>
                                                        <td>{{ $i++ }}</td>
                                                        <td><strong>{{ $emp->name }}</strong></td>
                                                        <td>{{ $emp->email }}</td>
                                                        <td>
                                                            @if($emp->department)
                                                                <span class="badge bg-light text-dark border">{{ $emp->department }}</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $emp->phone ?? '-' }}</td>
                                                        <td>
                                                            <a href="{{ route('business.employees.edit', $emp->id) }}"
                                                                style="font-size: 20px; color:#00ACEF !important"
                                                                class="text-primary me-2"
                                                                title="{{ __('messages.edit') }}">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            <a href="javascript:void(0);"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                onclick="return showConfirmToast('{{ route('business.employees.delete', $emp->id) }}')"
                                                                class="text-danger"
                                                                title="{{ __('messages.delete') }}">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> {{ __('messages.no_records_found') ?? 'No employees found.' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_delete') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_delete_employee') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <a href="#" id="confirm_url" class="btn btn-danger">{{ __('messages.delete') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
