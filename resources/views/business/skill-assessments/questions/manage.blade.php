@extends('layouts.business')

@section('title', __('messages.questions') ?? 'Questions')

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.skill_assessment') ?? 'Skill Assessment' }} - {{ __('messages.questions') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.skill-assessment.sections') }}">{{ __('messages.sections') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.questions') }}</li>
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
                            <h5 class="card-title mb-0">{{ __('messages.question_list') ?? 'Question List' }}
                                <span class="badge bg-secondary">{{ $questions->count() }}</span>
                            </h5>
                            <div>
                                <a href="{{ route('business.skill-assessment.sections') }}"
                                    class="btn btn-secondary btn-sm">
                                    <i class="bi bi-arrow-left"></i> {{ __('messages.back_to_sections') ?? 'Back' }}
                                </a>
                                <a href="{{ route('business.skill-assessment.questions.export', $section->id) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="bi bi-download"></i> {{ __('messages.export') ?? 'Export' }}
                                </a>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#importModal">
                                    <i class="bi bi-upload"></i> {{ __('messages.import') ?? 'Import' }}
                                </button>
                                <a href="{{ route('business.skill-assessment.questions.create', $section->id) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> {{ __('messages.add_question') ?? 'Add Question' }}
                                </a>
                                @if ($questions->count() > 0)
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteAll()">
                                        <i class="bi bi-trash"></i> {{ __('messages.delete_all') ?? 'Delete All' }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>{{ __('messages.section') }}:</strong> {{ $section->title }}
                        </div>

                        <table class="table table-bordered datatable">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>{{ __('messages.question') }}</th>
                                    <th width="120">{{ __('messages.type') }}</th>
                                    <th width="80">{{ __('messages.order') }}</th>
                                    <th width="80">{{ __('messages.required') }}</th>
                                    <th width="100">{{ __('messages.status') }}</th>
                                    <th width="150">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @foreach ($questions as $question)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ Str::limit($question->question_text, 60) }}</td>
                                        <td>
                                            <span
                                                class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</span>
                                        </td>
                                        <td>{{ $question->order }}</td>
                                        <td>
                                            @if ($question->is_required)
                                                <span class="badge bg-warning">{{ __('messages.yes') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('messages.no') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($question->is_active)
                                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('business.skill-assessment.questions.edit', [$section->id, $question->id]) }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="showConfirmToast('{{ route('business.skill-assessment.questions.delete', [$section->id, $question->id]) }}')"
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

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="bi bi-upload"></i> {{ __('messages.import_questions') ?? 'Import Questions' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('business.skill-assessment.questions.import', $section->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="import_file"
                                        class="form-label">{{ __('messages.select_csv_file') ?? 'Select CSV File' }} <span
                                            class="text-danger">*</span></label>
                                    <input type="file" name="file" id="import_file" class="form-control"
                                        accept=".csv,.xlsx,.xls" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i>
                                        {{ __('messages.instructions') ?? 'Instructions' }}</h6>
                                    <ol class="mb-0 ps-3 small">
                                        <li>{{ __('messages.download_example_first') ?? 'Download the example file first.' }}
                                        </li>
                                        <li>{{ __('messages.fill_in_details') ?? 'Fill in your question details.' }}</li>
                                        <li>{{ __('messages.upload_file') ?? 'Upload the file here.' }}</li>
                                    </ol>
                                </div>
                                <a href="{{ route('business.skill-assessment.questions.download-example') }}"
                                    class="btn btn-outline-primary w-100">
                                    <i class="bi bi-download"></i>
                                    {{ __('messages.download_example') ?? 'Download Example' }}
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-secondary mt-3">
                            <h6><i class="bi bi-file-earmark-spreadsheet"></i>
                                {{ __('messages.file_format') ?? 'Excel Format' }}</h6>
                            <ul class="mb-0 small">
                                <li>{{ __('messages.required_cols') ?? 'Required' }}: <code>Question Type</code>,
                                    <code>Question Text (EN)</code>
                                </li>
                                <li>{{ __('messages.optional_cols') ?? 'Optional' }}: <code>Question Text (FR)</code>,
                                    <code>Helper Text (EN/FR)</code>, <code>Order</code>, <code>Required</code>,
                                    <code>Status</code>
                                </li>
                                <li>{{ __('messages.options_format') ?? 'Options' }} (EN):
                                    <code>Option1:Weightage:correct|Option2:Weightage</code>
                                </li>
                                <li>{{ __('messages.options_format') ?? 'Options' }} (FR):
                                    <code>Option1FR|Option2FR</code>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('messages.close') ?? 'Close' }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> {{ __('messages.import') ?? 'Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_delete') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_delete_question') ?? 'Are you sure you want to delete this question?' }}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }

        function confirmDeleteAll() {
            Swal.fire({
                title: '{{ __('messages.are_you_sure') ?? 'Are you sure?' }}',
                text: '{{ __('messages.delete_all_questions_warning') ?? 'This will delete all questions. This cannot be undone!' }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __('messages.yes_delete_all') ?? 'Yes, delete all!' }}',
                cancelButtonText: '{{ __('messages.cancel') ?? 'Cancel' }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href =
                        '{{ route('business.skill-assessment.questions.delete-all', $section->id) }}';
                }
            });
        }
    </script>
@endsection
