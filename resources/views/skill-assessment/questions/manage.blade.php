@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        function update_status(el) {
            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('changeskillassessmentquestionstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg(
                        '{{ __('messages.question_status_updated') ?? 'Question status updated successfully' }}',
                        'success', '{{ __('messages.success') ?? 'Success' }}');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') ?? 'Something went wrong' }}', 'danger',
                        '{{ __('messages.danger') ?? 'Error' }}');
                }
            });
        }

        function filterBySection(sectionId) {
            var examTemplateId = '{{ $examTemplateId ?? '' }}';
            var url = '{{ route('manageskillassessmentquestions') }}';
            var params = [];
            if (examTemplateId) {
                params.push('exam_template_id=' + examTemplateId);
            }
            if (sectionId) {
                params.push('section_id=' + sectionId);
            }
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            window.location.href = url;
        }

        function confirmDeleteAll(sectionId) {
            Swal.fire({
                title: '{{ __('messages.are_you_sure') ?? 'Are you sure?' }}',
                text: '{{ __('messages.delete_all_warning') ?? 'This will delete all questions in this section. This action cannot be undone!' }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __('messages.yes_delete_it') ?? 'Yes, delete it!' }}',
                cancelButtonText: '{{ __('messages.cancel') ?? 'Cancel' }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ url('admin/skill-assessment/questions/delete-all') }}/' + sectionId;
                }
            });
        }
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.manage_questions') ?? 'Manage Questions' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</li>
                    @if (isset($examTemplate) && $examTemplate)
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.exams') ?? 'Exams' }}</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentsections', ['exam_template_id' => $examTemplate->id]) }}">{{ $examTemplate->title }}</a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active">{{ __('messages.questions') ?? 'Questions' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    {{-- Filter & Actions Card --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label for="section_filter"
                                        class="form-label">{{ __('messages.filter_by_section') ?? 'Filter by Section' }}</label>
                                    <select id="section_filter" class="form-select" onchange="filterBySection(this.value)">
                                        <option value="">{{ __('messages.all_sections') ?? 'All Sections' }}</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
                                                {{ $selectedSectionId == $section->id ? 'selected' : '' }}>
                                                {{ $section->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8 text-end mt-3 mt-md-0">
                                    {{-- Export Button (only if section selected) --}}
                                    @if ($selectedSectionId)
                                        <a href="{{ route('exportskillassessmentquestions', ['section_id' => $selectedSectionId]) }}"
                                            class="btn btn-success">
                                            <i class="bi bi-download"></i> {{ __('messages.export') ?? 'Export' }}
                                        </a>
                                        <button type="button" class="btn btn-danger"
                                            onclick="confirmDeleteAll({{ $selectedSectionId }})">
                                            <i class="bi bi-trash"></i> {{ __('messages.delete_all') ?? 'Delete All' }}
                                        </button>
                                    @endif

                                    {{-- Import Button --}}
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#importModal">
                                        <i class="bi bi-upload"></i> {{ __('messages.import') ?? 'Import' }}
                                    </button>

                                    {{-- Add Question Button --}}
                                    <a href="{{ route('createskillassessmentquestion', array_filter(['exam_template_id' => $examTemplateId ?? null, 'section_id' => $selectedSectionId ?? null])) }}"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus"></i> {{ __('messages.add_question') ?? 'Add Question' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Questions Table --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.questions') ?? 'Questions' }}
                                <span class="badge bg-secondary">{{ $questions->count() }}</span>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    @if ($questions->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{ __('messages.section') ?? 'Section' }}</th>
                                                        <th>{{ __('messages.question_text') ?? 'Question' }}</th>
                                                        <th>{{ __('messages.question_type') ?? 'Type' }}</th>
                                                        <th>{{ __('messages.options') ?? 'Options' }}</th>
                                                        <th>{{ __('messages.status') ?? 'Status' }}</th>
                                                        <th>{{ __('messages.action') ?? 'Action' }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($questions as $question)
                                                        <tr>
                                                            <td>{{ $question->order }}</td>
                                                            <td>
                                                                <span
                                                                    class="badge bg-info">{{ $question->section->title ?? 'N/A' }}</span>
                                                            </td>
                                                            <td>
                                                                {{ Str::limit($question->question_text, 40) }}
                                                                @if ($question->is_required)
                                                                    <span
                                                                        class="badge bg-danger">{{ __('messages.required') ?? 'Required' }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @switch($question->question_type)
                                                                    @case('radio')
                                                                        <span
                                                                            class="badge bg-primary">{{ __('messages.radio') ?? 'Radio' }}</span>
                                                                    @break

                                                                    @case('multi_select')
                                                                        <span
                                                                            class="badge bg-warning text-dark">{{ __('messages.checkbox') ?? 'Checkbox' }}</span>
                                                                    @break

                                                                    @case('open_text')
                                                                        <span
                                                                            class="badge bg-secondary">{{ __('messages.open_text') ?? 'Open Text' }}</span>
                                                                    @break
                                                                @endswitch
                                                            </td>
                                                            <td>{{ $question->options->count() }}</td>
                                                            <td>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input"
                                                                        onchange="update_status(this)" type="checkbox"
                                                                        {{ $question->is_active ? 'checked' : '' }}
                                                                        value="{{ $question->id }}">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('updateskillassessmentquestion', ['id' => $question->id]) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deleteskillassessmentquestion', ['id' => $question->id]) }}')"
                                                                    class="text-danger">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i>
                                            {{ __('messages.no_questions') ?? 'No questions found. Add your first question to get started.' }}
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

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="bi bi-upload"></i> {{ __('messages.import_questions') ?? 'Import Questions' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('importskillassessmentquestions') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                {{-- Section Selection --}}
                                <div class="mb-3">
                                    <label for="import_section"
                                        class="form-label">{{ __('messages.select_section') ?? 'Select Section' }} <span
                                            class="text-danger">*</span></label>
                                    <select name="skill_assessment_section_id" id="import_section" class="form-select"
                                        required>
                                        <option value="">{{ __('messages.select_section') ?? 'Select Section' }}
                                        </option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
                                                {{ $selectedSectionId == $section->id ? 'selected' : '' }}>
                                                {{ $section->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- File Upload --}}
                                <div class="mb-3">
                                    <label for="import_file"
                                        class="form-label">{{ __('messages.select_excel_csv') ?? 'Select Excel/CSV File' }}
                                        <span class="text-danger">*</span></label>
                                    <input type="file" name="file" id="import_file" class="form-control"
                                        accept=".xlsx,.xls,.csv" required>
                                    <div class="form-text">{{ __('messages.max_file_size') ?? 'Max file size: 5MB' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                {{-- Instructions --}}
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i>
                                        {{ __('messages.instructions') ?? 'Instructions' }}</h6>
                                    <ol class="mb-0 ps-3">
                                        <li>{{ __('messages.download_example_desc') ?? 'Download the example file to see the correct format.' }}
                                        </li>
                                        <li>{{ __('messages.fill_details') ?? 'Fill in your question details in the spreadsheet.' }}
                                        </li>
                                        <li>{{ __('messages.upload_file') ?? 'Upload the filled file here.' }}</li>
                                    </ol>
                                </div>

                                {{-- Download Example --}}
                                <a href="{{ route('downloadskillassessmentquestionsexample') }}"
                                    class="btn btn-outline-primary w-100">
                                    <i class="bi bi-download"></i>
                                    {{ __('messages.download_example') ?? 'Download Example' }}
                                </a>
                            </div>
                        </div>

                        {{-- Format Info --}}
                        <div class="alert alert-secondary mt-3">
                            <h6><i class="bi bi-file-earmark-spreadsheet"></i>
                                {{ __('messages.file_format_requirements') ?? 'File Format Requirements' }}</h6>
                            <ul class="mb-0">
                                <li>{{ __('messages.required_columns') ?? 'Required columns' }}: <code>Question
                                        Type</code>, <code>Question Text (EN)</code></li>
                                <li>{{ __('messages.optional_columns') ?? 'Optional columns' }}: <code>Question Text
                                        (FR)</code>,
                                    <code>Helper Text (EN)</code>, <code>Helper Text (FR)</code>,
                                    <code>Order</code>, <code>Required</code>, <code>Status</code>
                                </li>
                                <li>{{ __('messages.options_format') ?? 'Options Format' }} (EN):
                                    <code>Option1:Weightage:correct|Option2:Weightage</code>
                                </li>
                                <li>{{ __('messages.options_format') ?? 'Options Format' }} (FR):
                                    <code>Option1FR|Option2FR</code>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('messages.close') ?? 'Close' }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> {{ __('messages.import') ?? 'Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('partials.footer')
@endsection
