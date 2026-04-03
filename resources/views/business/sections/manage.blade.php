@extends('layouts.business')

@section('title', __('messages.sections'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.sections') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">{{ __('messages.section_list') }}</h5>
                            <div>
                                <a href="{{ route('business.sections.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> {{ __('messages.add_section') }}
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            {{ __('messages.sections_info') }}
                        </div>

                        <table class="table table-bordered datatable">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>{{ __('messages.title') }} (EN)</th>
                                    <th>{{ __('messages.title') }} (FR)</th>
                                    <th width="80">{{ __('messages.order') }}</th>
                                    <th width="100">{{ __('messages.status') }}</th>
                                    <th width="200">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @foreach ($sections as $section)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $section->title_en }}</td>
                                        <td>{{ $section->title_fr }}</td>
                                        <td>{{ $section->order }}</td>
                                        <td>
                                            @if ($section->is_active)
                                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('business.questions', $section->id) }}"
                                                class="btn btn-info btn-sm" title="{{ __('messages.manage_questions') }}">
                                                <i class="bi bi-list-ul"></i>
                                            </a>
                                            <a href="{{ route('business.sections.edit', $section->id) }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="showConfirmToast('{{ route('business.sections.delete', $section->id) }}')"
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
                    <a href="#" id="confirm_url" class="btn btn-danger">{{ __('messages.delete') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('customjs')
    <script>
        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection
