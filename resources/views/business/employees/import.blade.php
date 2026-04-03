@extends('layouts.business')

@section('title', __('messages.import_employees'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.import_employees') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('business.employees') }}">{{ __('messages.employees') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('messages.import') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.bulk_import_employees') }}</h5>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> {{ __('messages.file_format_requirements') }}</h6>
                            <ul class="mb-0">
                                <li>{{ __('messages.file_must_be') }} <strong>.xlsx, .xls, or .csv</strong></li>
                                <li>{{ __('messages.first_row_headers') }}</li>
                                <li>{{ __('messages.required_columns') }} <strong>name</strong>, <strong>email</strong>
                                </li>
                                <li>{{ __('messages.optional_columns') }} <strong>department</strong>,
                                    <strong>phone</strong></li>
                                <li>{{ __('messages.duplicate_emails_skipped') }}</li>
                            </ul>
                        </div>

                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6>{{ __('messages.sample_excel_format') }}</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>name</th>
                                            <th>email</th>
                                            <th>department</th>
                                            <th>phone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>John Doe</td>
                                            <td>john@example.com</td>
                                            <td>Sales</td>
                                            <td>1234567890</td>
                                        </tr>
                                        <tr>
                                            <td>Jane Smith</td>
                                            <td>jane@example.com</td>
                                            <td>HR</td>
                                            <td>0987654321</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <form action="{{ route('business.employees.import.process') }}" method="POST"
                            enctype="multipart/form-data" class="row g-3">
                            @csrf

                            <div class="col-12">
                                <label for="file" class="form-label">{{ __('messages.select_file') }} <span
                                        class="text-danger">*</span></label>
                                <input type="file" name="file"
                                    class="form-control {{ $errors->has('file') ? 'is-invalid' : '' }}" id="file"
                                    accept=".xlsx,.xls,.csv" required>
                                @if ($errors->has('file'))
                                    <div class="invalid-feedback">{{ $errors->first('file') }}</div>
                                @endif
                                <small class="text-muted">{{ __('messages.max_file_size') }}</small>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> {{ __('messages.import_employees') }}
                                </button>
                                <a href="{{ route('business.employees') }}"
                                    class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
