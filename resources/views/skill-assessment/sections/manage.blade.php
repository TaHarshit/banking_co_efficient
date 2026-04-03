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
            $.post('{{ route('changeskillassessmentsectionstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('Section status updated successfully', 'success', 'Success');
                } else {
                    NotifMsg('Something went wrong', 'danger', 'Danger');
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
            <h1>{{ __('messages.skill_assessment_sections') ?? 'Skill Assessment Sections' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</a>
                    </li>
                    @if (isset($examTemplate) && $examTemplate)
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.exams') ?? 'Exams' }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $examTemplate->title }} -
                            {{ __('messages.sections') ?? 'Sections' }}</li>
                    @else
                        <li class="breadcrumb-item active">{{ __('messages.sections') ?? 'Sections' }}</li>
                    @endif
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.manage_sections') ?? 'Manage Sections' }}
                                @if (isset($examTemplate) && $examTemplate)
                                    <span class="text-muted"> - {{ $examTemplate->title }}</span>
                                @endif
                                <a href="{{ route('createskillassessmentsection') }}{{ isset($examTemplateId) && $examTemplateId ? '?exam_template_id=' . $examTemplateId : '' }}"
                                    class="btn btn-primary" style="float: right;">
                                    <i class="bi bi-plus"></i> {{ __('messages.add_section') ?? 'Add Section' }}
                                </a>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    @if ($sections->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{ __('messages.title') ?? 'Title' }}</th>
                                                        <th>{{ __('messages.description') ?? 'Description' }}</th>
                                                        <th>{{ __('messages.questions') ?? 'Questions' }}</th>
                                                        <th>{{ __('messages.status') ?? 'Status' }}</th>
                                                        <th>{{ __('messages.action') ?? 'Action' }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sections as $index => $section)
                                                        <tr>
                                                            <td>{{ $section->order }}</td>
                                                            <td>{{ $section->title }}</td>
                                                            <td>{{ Str::limit($section->description, 50) }}</td>
                                                            <td>
                                                                <a href="{{ route('manageskillassessmentquestions', array_filter(['section_id' => $section->id, 'exam_template_id' => $examTemplate->id ?? null])) }}"
                                                                    class="btn btn-sm btn-info">
                                                                    <i class="bi bi-list-ul"></i>
                                                                    {{ __('messages.view_questions') ?? 'View Questions' }}
                                                                    ({{ $section->questions_count }})
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input"
                                                                        onchange="update_status(this)" type="checkbox"
                                                                        {{ $section->is_active ? 'checked' : '' }}
                                                                        value="{{ $section->id }}">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('updateskillassessmentsection', ['id' => $section->id]) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deleteskillassessmentsection', ['id' => $section->id]) }}')"
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
                                            {{ __('messages.no_sections') ?? 'No sections found. Create your first section to get started.' }}
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
