@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.exam_results') ?? 'Exam Results' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</li>
                    <li class="breadcrumb-item active">{{ __('messages.exam_results') ?? 'Exam Results' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.filter_results') ?? 'Filter Results' }}</h5>
                            <form method="GET" action="{{ route('manageskillassessmentexams') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('messages.exams') ?? 'Exam' }}</label>
                                    <select name="exam_template_id" class="form-select">
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
                                    <select name="section_id" class="form-select">
                                        <option value="">{{ __('messages.all_sections') ?? 'All Sections' }}</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
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
                                            {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                            Completed</option>
                                        <option value="evaluated" {{ request('status') == 'evaluated' ? 'selected' : '' }}>
                                            Evaluated</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('messages.filter') ?? 'Filter' }}</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.exam_submissions') ?? 'Exam Submissions' }}</h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable">
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
                                                @php $i = 1; @endphp
                                                @foreach ($exams as $exam)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ optional($exam->user)->name ?? 'N/A' }}
                                                            {{ optional($exam->user)->surname ?? '' }}</td>
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
                                                        </td>
                                                        <td>
                                                            @if ($exam->status == 'in_progress')
                                                                <span class="badge bg-secondary">In Progress</span>
                                                            @elseif($exam->status == 'completed')
                                                                <span class="badge bg-primary">Completed</span>
                                                            @else
                                                                <span class="badge bg-success">Evaluated</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $exam->completed_at ? $exam->completed_at->format('d M Y H:i') : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('viewskillassessmentexam', ['id' => $exam->id]) }}"
                                                                class="btn btn-sm btn-info">
                                                                <i class="bi bi-eye"></i>
                                                                {{ __('messages.view') ?? 'View' }}
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @php $i++; @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-center">
                                            {{ $exams->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
