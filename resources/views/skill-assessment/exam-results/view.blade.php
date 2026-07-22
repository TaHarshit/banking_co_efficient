@extends('layouts.app')
@section('pagewisestyle')
    <style>
        .question-card {
            margin-bottom: 1.5rem;
        }

        .answer-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }

        .correct-option {
            color: #198754;
            font-weight: bold;
        }

        .selected-option {
            background-color: #e7f1ff;
            border-left: 3px solid #0d6efd;
            padding-left: 0.5rem;
        }

        .open-text-answer {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .score-input {
            width: 80px;
        }
    </style>
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script>
        function scoreAnswer(answerId) {
            var score = document.getElementById('score_' + answerId).value;
            $.post('{{ route('scoreskillassessmentanswer') }}', {
                _token: '{{ csrf_token() }}',
                answer_id: answerId,
                score: score
            }, function(data) {
                if (data.success) {
                    NotifMsg('Score updated successfully', 'success', 'Success');
                    // Update totals
                    document.getElementById('total_score').textContent = data.exam.total_score;
                    document.getElementById('percentage').textContent = parseFloat(data.exam.percentage).toFixed(
                        1) + '%';
                    if (document.getElementById('score_scale_5') && data.exam.score_scale_5 !== undefined) {
                        document.getElementById('score_scale_5').textContent = parseFloat(data.exam.score_scale_5).toFixed(2);
                    }
                    document.getElementById('exam_status').textContent = data.exam.status.charAt(0).toUpperCase() +
                        data.exam.status.slice(1);
                } else {
                    NotifMsg('Something went wrong', 'danger', 'Error');
                }
            }).fail(function() {
                NotifMsg('Failed to update score', 'danger', 'Error');
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
            <h1>{{ __('messages.view_exam_result') ?? 'View Exam Result' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('manageskillassessmentexams') }}">{{ __('messages.skill_assessment_results') ?? 'Skill Assessment Results' }}</a>
                    </li>
                    <li class="breadcrumb-item">{{ __('messages.view') ?? 'View' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.exam_details') ?? 'Exam Details' }}</h5>
                            <table class="table">
                                <tr>
                                    <th>{{ __('messages.user') ?? 'User' }}</th>
                                    <td>{{ $exam->user->name ?? 'N/A' }} {{ $exam->user->surname ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.email') ?? 'Email' }}</th>
                                    <td>{{ $exam->user->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.exams') ?? 'Exam' }}</th>
                                    <td>{{ $exam->examTemplate->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.section') ?? 'Section' }}</th>
                                    <td>{{ $exam->section->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.started_at') ?? 'Started At' }}</th>
                                    <td>{{ $exam->started_at ? $exam->started_at->format('d M Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.completed_at') ?? 'Completed At' }}</th>
                                    <td>{{ $exam->completed_at ? $exam->completed_at->format('d M Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.score') ?? 'Score' }}</th>
                                    <td><span id="total_score">{{ $exam->total_score }}</span> / {{ $exam->max_score }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.percentage') ?? 'Percentage' }}</th>
                                    <td>
                                        <span id="percentage"
                                            class="badge {{ $exam->percentage >= 70 ? 'bg-success' : ($exam->percentage >= 40 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ number_format($exam->percentage, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Score (1-5 Scale)</th>
                                    <td>
                                        <span id="score_scale_5" class="fw-bold text-primary">
                                            {{ number_format($exam->score_scale_5, 2) }}
                                        </span> / 5.0
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.status') ?? 'Status' }}</th>
                                    <td><span id="exam_status">{{ ucfirst($exam->status) }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if(!empty($exam->section_scores))
                        <div class="card mt-3">
                            <div class="card-body">
                                <h5 class="card-title">Section-Wise Scores</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Section</th>
                                                <th>Score</th>
                                                <th>%</th>
                                                <th>1-5 Scale</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($exam->section_scores as $sec)
                                                <tr>
                                                    <td><strong>{{ $sec['section_title'] }}</strong></td>
                                                    <td>{{ $sec['total_score'] }} / {{ $sec['max_score'] }}</td>
                                                    <td>
                                                        <span class="badge {{ $sec['percentage'] >= 70 ? 'bg-success' : ($sec['percentage'] >= 40 ? 'bg-warning' : 'bg-danger') }}">
                                                            {{ number_format($sec['percentage'], 1) }}%
                                                        </span>
                                                    </td>
                                                    <td><span class="fw-bold">{{ number_format($sec['score_scale_5'], 2) }}</span> / 5.0</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.answers') ?? 'Answers' }}</h5>

                            @foreach ($exam->answers as $index => $answer)
                                <div class="question-card card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span><strong>Q{{ $index + 1 }}:</strong>
                                            {{ $answer->question->question_text }}</span>
                                        <span
                                            class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $answer->question->question_type)) }}</span>
                                    </div>
                                    <div class="card-body">
                                        @if ($answer->question->question_type == 'open_text')
                                            <div class="open-text-answer">
                                                <strong>{{ __('messages.answer') ?? 'Answer' }}:</strong>
                                                <p class="mb-2">{{ $answer->text_answer ?? 'No answer provided' }}</p>
                                                <hr>
                                                <div class="d-flex align-items-center gap-2">
                                                    <label><strong>{{ __('messages.score') ?? 'Score' }}:</strong></label>
                                                    <input type="number" step="0.01" min="0"
                                                        id="score_{{ $answer->id }}" class="form-control score-input"
                                                        value="{{ $answer->score }}">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        onclick="scoreAnswer({{ $answer->id }})">
                                                        {{ __('messages.update_score') ?? 'Update Score' }}
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="answer-box">
                                                @foreach ($answer->question->options as $option)
                                                    @php
                                                        $isSelected = in_array(
                                                            $option->id,
                                                            $answer->selected_option_ids ?? [],
                                                        );
                                                    @endphp
                                                    <div class="mb-2 {{ $isSelected ? 'selected-option' : '' }}">
                                                        @if ($answer->question->question_type == 'radio')
                                                            <i
                                                                class="bi {{ $isSelected ? 'bi-record-circle-fill text-primary' : 'bi-circle' }}"></i>
                                                        @else
                                                            <i
                                                                class="bi {{ $isSelected ? 'bi-check-square-fill text-primary' : 'bi-square' }}"></i>
                                                        @endif
                                                        {{ $option->option_text }}
                                                        <span class="text-muted">(Weight: {{ $option->weightage }})</span>
                                                    </div>
                                                @endforeach
                                                <div class="mt-2">
                                                    <strong>{{ __('messages.score_earned') ?? 'Score Earned' }}:
                                                        {{ $answer->score }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <div class="mt-4">
                                <a href="{{ route('manageskillassessmentexams') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> {{ __('messages.back_to_list') ?? 'Back to List' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
