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
            $.post('{{ route('changesectionstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('{{ __('messages.section_status_updated') }}', 'success', 'Success');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', 'Danger');
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
            <h1>{{ __('messages.manage_sections') }}</h1>
            <p class="form-text text-muted">Onboarding Section</p>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.personalized_experience') }}</li>
                    <li class="breadcrumb-item active">{{ __('messages.sections') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.manage_sections') }} <span class="form-text text-muted">(Onboarding)</span>
                                <a href="{{ route('createsection') }}" class="btn btn-primary" style="float: right;">
                                    <i class="bi bi-plus"></i> {{ __('messages.add_section') }}
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
                                                        <th>{{ __('messages.section_title') }} (EN)</th>
                                                        <th>{{ __('messages.section_title') }} (FR)</th>
                                                        <th>{{ __('messages.questions') }}</th>
                                                        <th>{{ __('messages.status') }}</th>
                                                        <th>{{ __('messages.action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sections as $index => $section)
                                                        <tr>
                                                            <td>{{ $section->order }}</td>
                                                            <td>{{ $section->title_en }}</td>
                                                            <td>{{ $section->title_fr }}</td>
                                                            <td>
                                                                <a href="{{ route('managequestions', ['section_id' => $section->id]) }}"
                                                                    class="btn btn-sm btn-info">
                                                                    <i class="bi bi-list-ul"></i>
                                                                    {{ __('messages.view_questions') }}
                                                                    ({{ $section->questions->count() }})
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
                                                                <a href="{{ route('updatesection', ['id' => $section->id]) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deletesection', ['id' => $section->id]) }}')"
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
                                            <i class="bi bi-info-circle"></i> {{ __('messages.no_sections') }}
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
