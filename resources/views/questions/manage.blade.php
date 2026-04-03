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
            $.post('{{ route('changequestionstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('{{ __('messages.question_status_updated') }}', 'success', 'Success');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', 'Danger');
                }
            });
        }

        function showDeleteAllConfirm(url) {
            Swal.fire({
                title: '{{ __('messages.are_you_sure') }}',
                text: "{{ __('messages.delete_all_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('messages.yes_delete_it') }}',
                cancelButtonText: '{{ __('messages.cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
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
            <h1>{{ __('messages.manage_questions') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('managesections') }}">{{ __('messages.sections') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $section->title_en }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    {{-- Section Info Card --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">{{ $section->title_en }} / {{ $section->title_fr }}</h5>
                                    @if ($section->header_en)
                                        <p class="text-muted mb-0">{{ $section->header_en }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('managesections') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> {{ __('messages.back_to_sections') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Questions Table --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.questions') }}
                                <div style="float: right;">
                                    <a href="{{ route('exportquestions', ['section_id' => $section->id]) }}"
                                        class="btn btn-success me-2">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('messages.export') }}
                                    </a>
                                    <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal"
                                        data-bs-target="#importModal">
                                        <i class="bi bi-upload"></i> {{ __('messages.import') }}
                                    </button>
                                    @if ($questions->count() > 0)
                                        <button type="button" class="btn btn-danger me-2"
                                            onclick="showDeleteAllConfirm('{{ route('deleteallquestions', ['section_id' => $section->id]) }}')">
                                            <i class="bi bi-trash"></i> {{ __('messages.delete_all') }}
                                        </button>
                                    @endif
                                    <a href="{{ route('createquestion', ['section_id' => $section->id]) }}"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus"></i> {{ __('messages.add_question') }}
                                    </a>
                                </div>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    @if ($questions->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table datatable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{ __('messages.question_text') }} (EN)</th>
                                                        <th>{{ __('messages.question_type') }}</th>
                                                        <th>{{ __('messages.options') }}</th>
                                                        <th>{{ __('messages.status') }}</th>
                                                        <th>{{ __('messages.action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($questions as $question)
                                                        <tr>
                                                            <td>{{ $question->order }}</td>
                                                            <td>
                                                                {{ Str::limit($question->question_text_en, 50) }}
                                                                @if ($question->is_required)
                                                                    <span
                                                                        class="badge bg-danger">{{ __('messages.question_required') }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @switch($question->question_type)
                                                                    @case('single_select')
                                                                        <span
                                                                            class="badge bg-primary">{{ __('messages.single_select') }}</span>
                                                                    @break

                                                                    @case('multi_select')
                                                                        <span
                                                                            class="badge bg-info">{{ __('messages.multi_select') }}</span>
                                                                    @break

                                                                    @case('rating_scale')
                                                                        <span
                                                                            class="badge bg-warning">{{ __('messages.rating_scale') }}</span>
                                                                    @break

                                                                    @case('text_input')
                                                                        <span
                                                                            class="badge bg-secondary">{{ __('messages.text_input') }}</span>
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
                                                                <a href="{{ route('updatequestion', ['id' => $question->id]) }}"
                                                                    style="font-size: 20px; color:#00ACEF !important"
                                                                    class="text-primary">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                <a href="javascript:void(0);"
                                                                    style="font-size: 20px; color:#EE6C4D !important;"
                                                                    onclick="return showConfirmToast('{{ route('deletequestion', ['id' => $question->id]) }}')"
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
                                            <i class="bi bi-info-circle"></i> {{ __('messages.no_questions') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('importquestions') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title" id="importModalLabel">{{ __('messages.import_questions') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 text-end">
                                <a href="{{ route('downloadexample') }}" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-download"></i> {{ __('messages.download_example') }}
                                </a>
                            </div>
                            <div class="mb-3">
                                <label for="file" class="form-label">{{ __('messages.select_excel_csv') }}</label>
                                <input class="form-control" type="file" id="file" name="file"
                                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                    required>
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <strong>{{ __('messages.instructions') }}:</strong><br>
                                    1. {{ __('messages.download_example_desc') }}<br>
                                    2. {{ __('messages.fill_details') }}<br>
                                    3. {{ __('messages.upload_file') }}<br>
                                    <br>
                                    <strong>{{ __('messages.options_format') }}:</strong> <code>Option EN : Option FR |
                                        Option EN : Option FR</code>
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('messages.import') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    @include('partials.footer')
@endsection
