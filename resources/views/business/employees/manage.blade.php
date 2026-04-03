@extends('layouts.business')

@section('title', __('messages.employees'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.employees') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('messages.employees') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">{{ __('messages.employee_list') }}</h5>
                            <div>
                                <a href="{{ route('business.employees.import') }}" class="btn btn-success btn-sm">
                                    <i class="bi bi-file-earmark-excel"></i> {{ __('messages.import_excel') }}
                                </a>
                                <a href="{{ route('business.employees.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> {{ __('messages.add_employee') }}
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            {{ __('messages.employees_register_hint') }}
                            <strong>{{ $business->business_code }}</strong>
                        </div>

                        <table class="table table-bordered datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('messages.email') }}</th>
                                    <th>{{ __('messages.department') }}</th>
                                    <th>{{ __('messages.phone') }}</th>
                                    <th>{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @foreach ($employees as $emp)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $emp->name }}</td>
                                        <td>{{ $emp->email }}</td>
                                        <td>{{ $emp->department ?? '-' }}</td>
                                        <td>{{ $emp->phone ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('business.employees.edit', $emp->id) }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="showConfirmToast('{{ route('business.employees.delete', $emp->id) }}')"
                                                class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Confirm Modal -->
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

@section('customjs')
    <script>
        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection
