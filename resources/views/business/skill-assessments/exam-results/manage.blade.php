@extends('layouts.business')

@section('title', __('messages.exam_results') ?? 'Exam Results')

@section('customjs')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('exam_template_id');
            const sectionSelect = document.getElementById('section_id');

            if (templateSelect && sectionSelect) {
                function updateSectionOptions() {
                    const selectedTemplate = templateSelect.value;
                    const options = sectionSelect.querySelectorAll('option');

                    options.forEach(opt => {
                        if (!opt.value) {
                            opt.hidden = false;
                            opt.disabled = false;
                            return;
                        }
                        const optTemplate = opt.getAttribute('data-template');
                        if (!selectedTemplate || optTemplate === selectedTemplate) {
                            opt.hidden = false;
                            opt.disabled = false;
                        } else {
                            opt.hidden = true;
                            opt.disabled = true;
                        }
                    });

                    // If currently selected option is now hidden/disabled, reset section selection
                    const currentSelected = sectionSelect.selectedOptions[0];
                    if (currentSelected && currentSelected.disabled) {
                        sectionSelect.value = '';
                    }
                }

                templateSelect.addEventListener('change', updateSectionOptions);
                updateSectionOptions();
            }
        });
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.exam_results') ?? 'Exam Results' }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</li>
                <li class="breadcrumb-item active">{{ __('messages.exam_results') ?? 'Exam Results' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.filter_results') ?? 'Filter Results' }}</h5>
                        <form method="GET" action="{{ route('business.skill-assessment.exam-results') }}" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.user') ?? 'Candidate' }}</label>
                                <input type="text" name="search" class="form-control"
                                    placeholder="{{ __('messages.search_candidate') ?? 'Search candidate...' }}"
                                    value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.exams') ?? 'Exam' }}</label>
                                <select name="exam_template_id" id="exam_template_id" class="form-select">
                                    <option value="">{{ __('messages.all_exams') ?? 'All Exams' }}</option>
                                    @foreach ($examTemplates as $template)
                                        <option value="{{ $template->id }}"
                                            {{ request('exam_template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.section') ?? 'Section' }}</label>
                                <select name="section_id" id="section_id" class="form-select">
                                    <option value="">{{ __('messages.all_sections') ?? 'All Sections' }}</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}"
                                            data-template="{{ $section->skill_assessment_exam_template_id }}"
                                            {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                            {{ $section->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.status') ?? 'Status' }}</label>
                                <select name="status" class="form-select">
                                    <option value="">{{ __('messages.all_statuses') ?? 'All Statuses' }}</option>
                                    <option value="in_progress"
                                        {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('messages.in_progress') }}</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                        {{ __('messages.completed') }}</option>
                                    <option value="evaluated" {{ request('status') == 'evaluated' ? 'selected' : '' }}>
                                        {{ __('messages.evaluated') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.date_from') ?? 'Date From' }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.date_to') ?? 'Date To' }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> {{ __('messages.filter') ?? 'Filter' }}
                                </button>
                                <a href="{{ route('business.skill-assessment.exam-results') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('messages.reset') ?? 'Reset' }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <span>{{ __('messages.exam_submissions') ?? 'Exam Submissions' }}</span>
                            <span class="badge bg-secondary text-light">{{ $exams->total() }}</span>
                        </h5>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ __('messages.user') ?? 'User' }}</th>
                                                <th>{{ __('messages.exams') ?? 'Exam' }}</th>
                                                <th>{{ __('messages.score') ?? 'Score' }}</th>
                                                <th>{{ __('messages.percentage') ?? 'Percentage' }}</th>
                                                <th>{{ __('messages.status') ?? 'Status' }}</th>
                                                <th>{{ __('messages.submitted_at') ?? 'Submitted At' }}</th>
                                                <th>{{ __('messages.action') ?? 'Action' }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($exams as $exam)
                                                <tr>
                                                    <td>{{ ($exams->currentPage() - 1) * $exams->perPage() + $loop->iteration }}</td>
                                                    <td>
                                                        <div class="fw-semibold">
                                                            {{ optional($exam->user)->name ?? 'N/A' }}
                                                            {{ optional($exam->user)->surname ?? '' }}
                                                        </div>
                                                        @if(optional($exam->user)->email)
                                                            <small class="text-muted">{{ $exam->user->email }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($exam->examTemplate)
                                                            <span
                                                                class="badge bg-primary">{{ $exam->examTemplate->title }}</span>
                                                        @elseif($exam->section)
                                                            <span
                                                                class="badge bg-info">{{ $exam->section->title }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $exam->total_score }} / {{ $exam->max_score }}</td>
                                                    <td>
                                                        <span
                                                            class="badge {{ $exam->percentage >= 70 ? 'bg-success' : ($exam->percentage >= 40 ? 'bg-warning' : 'bg-danger') }}">
                                                            {{ number_format($exam->percentage, 1) }}%
                                                        </span>
                                                        <small class="text-muted d-block font-monospace">{{ __('messages.score_1_5') }}: {{ number_format($exam->score_scale_5, 2) }}</small>
                                                    </td>
                                                    <td>
                                                        @if ($exam->status == 'in_progress')
                                                            <span class="badge bg-secondary">{{ __('messages.in_progress') }}</span>
                                                        @elseif($exam->status == 'completed')
                                                            <span class="badge bg-primary">{{ __('messages.completed') }}</span>
                                                        @else
                                                            <span class="badge bg-success">{{ __('messages.evaluated') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $exam->completed_at ? $exam->completed_at->format('d M Y H:i') : ($exam->created_at ? $exam->created_at->format('d M Y H:i') : 'N/A') }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('business.skill-assessment.exam-results.view', ['id' => $exam->id]) }}"
                                                            class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                            {{ __('messages.view') ?? 'View' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center py-4 text-muted">
                                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                        {{ __('messages.no_data') ?? 'No exam submissions found matching the criteria.' }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="mt-2">
                                        {{ $exams->appends(request()->all())->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
