@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        function update_status(el) {
            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('changeskillassessmentexamtemplatestatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('Exam template status updated successfully', 'success', 'Success');
                } else {
                    NotifMsg('Something went wrong', 'danger', 'Danger');
                }
            });
        }
        function filterExamsBySource(source) {
            var url = '{{ route('manageskillassessmentexamtemplates') }}';
            if (source) {
                url += '?source=' + source;
            }
            window.location.href = url;
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
            <h1>{{ __('messages.skill_assessment_exams') ?? 'Skill Assessment Exams' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</li>
                    <li class="breadcrumb-item active">{{ __('messages.exams') ?? 'Exams' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.manage_exams') ?? 'Manage Exams' }}
                                <a href="{{ route('createskillassessmentexamtemplate') }}" class="btn btn-primary"
                                    style="float: right;">
                                    <i class="bi bi-plus"></i> {{ __('messages.add_exam') ?? 'Add Exam' }}
                                </a>
                            </h5>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="exam_source_filter" class="form-label">{{ __('messages.filter_by_type') ?? 'Type' }}</label>
                                    <select id="exam_source_filter" class="form-select" onchange="filterExamsBySource(this.value)">
                                        <option value="">All</option>
                                        <option value="global" {{ (isset($source) && $source == 'global') ? 'selected' : '' }}>{{ __('messages.global') ?? 'Global' }}</option>
                                        <option value="business" {{ (isset($source) && $source == 'business') ? 'selected' : '' }}>{{ __('messages.business') ?? 'Business' }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    @if ($examTemplates->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{ __('messages.title') ?? 'Title' }}</th>
                                                        <th>{{ __('messages.source') ?? 'Source' }}</th>
                                                        <th>{{ __('messages.exam_level') ?? 'Exam Level' }}</th>
                                                        <th>{{ __('messages.tags') ?? 'Tags' }}</th>
                                                        <th>{{ __('messages.description') ?? 'Description' }}</th>
                                                        <th>{{ __('messages.duration') ?? 'Duration' }}</th>
                                                        <th>{{ __('messages.passing_percentage') ?? 'Passing %' }}</th>
                                                        <th>{{ __('messages.sections') ?? 'Sections' }}</th>
                                                        <th>{{ __('messages.status') ?? 'Status' }}</th>
                                                        <th>{{ __('messages.action') ?? 'Action' }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($examTemplates as $index => $template)
                                                        <tr>
                                                            <td>{{ $template->order }}</td>
                                                            <td>{{ $template->title }}</td>
                                                            <td>
                                                                @if($template->business_id)
                                                                    <span class="badge bg-info">{{ __('messages.business') ?? 'Business' }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ __('messages.global') ?? 'Global' }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($template->exam_level)
                                                                    <span class="badge bg-primary">{{ ucfirst($template->exam_level) }}</span>
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($template->tags && is_array($template->tags))
                                                                    @foreach ($template->tags as $tag)
                                                                        <span class="badge bg-secondary me-1">{{ $tag }}</span>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ Str::limit($template->description, 50) }}</td>
                                                            <td>
                                                                @if ($template->duration_minutes)
                                                                    {{ $template->duration_minutes }} min
                                                                @else
                                                                    <span class="text-muted">No limit</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($template->passing_percentage)
                                                                    {{ $template->passing_percentage }}%
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('manageskillassessmentsections') }}?exam_template_id={{ $template->id }}"
                                                                    class="btn btn-sm btn-info">
                                                                    <i class="bi bi-list-ul"></i>
                                                                    {{ __('messages.view_sections') ?? 'View Sections' }}
                                                                    ({{ $template->sections_count }})
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input"
                                                                        onchange="update_status(this)" type="checkbox"
                                                                        {{ $template->is_active ? 'checked' : '' }}
                                                                        value="{{ $template->id }}">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('updateskillassessmentexamtemplate', ['id' => $template->id]) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deleteskillassessmentexamtemplate', ['id' => $template->id]) }}')"
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
                                            {{ __('messages.no_exams') ?? 'No exams found. Create your first exam to get started.' }}
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
    @include('partials.footer')
@endsection
