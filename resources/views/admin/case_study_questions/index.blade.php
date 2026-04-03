@extends('layouts.app')

@include('partials.headerfiles')
@include('partials.footerfiles')

@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.case_study_questions') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.case_study_questions') }}</li>
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
                            <h5 class="card-title">{{ __('messages.case_study_questions') }}
                                <div style="float: right;">
                                    <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal"
                                        data-bs-target="#importModal">
                                        <i class="bi bi-upload"></i> {{ __('messages.import') }}
                                    </button>
                                    <a href="{{ route('admin.case_study_questions.create') }}"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus"></i> {{ __('messages.add_question') }}
                                    </a>
                                </div>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    @if ($questions->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('messages.section') }}</th>
                                                        <th>{{ __('messages.question') }} (EN)</th>
                                                        <th>{{ __('messages.question') }} (FR)</th>
                                                        <th>{{ __('messages.options') }}</th>
                                                        <th>{{ __('messages.action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($questions as $question)
                                                        <tr>
                                                            <td>{{ $question->section_name }}</td>
                                                            <td>{{ Str::limit($question->question_en, 50) }}</td>
                                                            <td>{{ Str::limit($question->question_fr, 50) }}</td>
                                                            <td>{{ $question->options->count() }}</td>
                                                            <td>
                                                                <a href="{{ route('admin.case_study_questions.edit', $question->id) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary me-2">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="{{ route('admin.case_study_questions.destroy', $question->id) }}"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return confirm('{{ __('messages.confirm_delete_question') }}')"
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
                                            <i class="bi bi-info-circle"></i> No questions found.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.case_study_questions.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="importModalLabel">Import Questions</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="file" class="form-label">{{ __('messages.select_excel_csv') }}</label>
                                <input class="form-control" type="file" id="file" name="file"
                                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                    required>
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <strong>Format Header (Row 1):</strong><br>
                                    Section | Q EN | Q FR | Opt1 EN | Opt1 FR | Opt1 Correct | Opt2 EN | Opt2 FR | Opt2 Correct | Opt3 EN | Opt3 FR | Opt3 Correct | Opt4 EN | Opt4 FR | Opt4 Correct<br><br>
                                    Write 'Yes' or 1 for the correct option.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('messages.import') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    @include('partials.footer')
@endsection
