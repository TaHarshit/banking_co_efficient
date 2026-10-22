@extends('layouts.business')
@section('title', __('messages.manage_sections'))
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        function update_status(el) {
            var status = el.checked ? 1 : 0;
            $.post('{{ route('business.sections.status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    if (typeof NotifMsg === 'function') {
                        NotifMsg('{{ __('messages.section_status_updated') }}', 'success', 'Success');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '{{ __('messages.section_status_updated') }}',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                } else {
                    if (typeof NotifMsg === 'function') {
                        NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', 'Danger');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: '{{ __('messages.something_went_wrong') }}',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            });
        }

        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.manage_sections') }}</h1>
        <p class="form-text text-muted">{{ __('messages.onboarding_section') }}</p>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item">{{ __('messages.personalized_experience') }}</li>
                <li class="breadcrumb-item active">{{ __('messages.sections') }}</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.manage_sections') }} <span class="form-text text-muted">({{ __('messages.onboarding') }})</span>
                            <a href="{{ route('business.sections.create') }}" class="btn btn-primary" style="float: right;">
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
                                                            <a href="{{ route('business.questions', $section->id) }}"
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
                                                            <a href="{{ route('business.sections.edit', $section->id) }}"
                                                                style="font-size: 20px; color:#00ACEF !important"
                                                                class="text-primary me-2">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            <a href="javascript:void(0);"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                onclick="return showConfirmToast('{{ route('business.sections.delete', $section->id) }}')"
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

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_delete') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_delete_section') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <a href="" id="confirm_url" class="btn btn-danger">{{ __('messages.delete') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
