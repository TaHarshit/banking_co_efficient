@extends('layouts.app')

@include('partials.headerfiles')
@include('partials.footerfiles')

@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.business_policies') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.business_policies') }}</li>
                </ol>
            </nav>
        </div>
        
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pt-3 flex-wrap gap-2">
                                <h5 class="card-title m-0 p-0">{{ __('messages.business_policies') }}</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <form method="GET" action="{{ route('admin.case_study_questions.index') }}" class="d-inline-flex align-items-center">
                                        <select name="business_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                                            <option value="">{{ __('messages.all_businesses') }}</option>
                                            <option value="global" {{ request('business_id') === 'global' ? 'selected' : '' }}>{{ __('messages.global_default') }}</option>
                                            @foreach ($businesses as $b)
                                                <option value="{{ $b->id }}" {{ request('business_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#importModal">
                                        <i class="bi bi-upload"></i> {{ __('messages.import') }}
                                    </button>
                                    <a href="{{ route('admin.case_study_questions.create') }}"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus"></i> {{ __('messages.add_question') }}
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    @if ($questions->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('messages.bank_or_business') }}</th>
                                                        <th>{{ __('messages.section') }} (EN)</th>
                                                        <th>{{ __('messages.section') }} (FR)</th>
                                                        <th>{{ __('messages.question') }} (EN)</th>
                                                        <th>{{ __('messages.question') }} (FR)</th>
                                                        <th>{{ __('messages.options') }}</th>
                                                        <th>{{ __('messages.action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($questions as $question)
                                                        <tr>
                                                            <td>
                                                                @if ($question->business)
                                                                    <span class="badge bg-primary">{{ $question->business->name }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ __('messages.global_default') }}</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $question->section_name_en ?: $question->section_name }}</td>
                                                            <td>{{ $question->section_name_fr ?: $question->section_name }}</td>
                                                            <td>{{ Str::limit($question->question_en, 45) }}</td>
                                                            <td>{{ Str::limit($question->question_fr, 45) }}</td>
                                                            <td>{{ $question->options->count() }}</td>
                                                            <td>
                                                                <a href="{{ route('admin.case_study_questions.edit', $question->id) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary me-2">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="{{ route('admin.case_study_questions.destroy', $question->id) }}"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return confirm('{{ __('messages.confirm_delete_policy_question') }}')"
                                                                    class="text-danger">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-3">
                                            {{ $questions->links() }}
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i> No policy questions found.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.case_study_questions.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('messages.import') }} {{ __('messages.business_policies') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.bank_or_business') }}</label>
                            <select name="business_id" class="form-select">
                                <option value="">{{ __('messages.global_default') }}</option>
                                @foreach ($businesses as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel / CSV File</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @include('partials.footer')
@endsection
