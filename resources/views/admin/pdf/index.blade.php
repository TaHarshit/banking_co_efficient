@extends('layouts.app')
@section('pagewisestyle')
    <!-- Page Wise Style Sheet -->
@endsection
@section('pagewisescript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#pdfUploadForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalBtnText = $submitBtn.html();

                $submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading & Processing...'
                    ).prop('disabled', true);

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitBtn.html(originalBtnText).prop('disabled', false);

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonColor: '#3085d6',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Something went wrong',
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitBtn.html(originalBtnText).prop('disabled', false);

                        var errorMessage = 'An error occurred during upload.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: errorMessage,
                        });
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            });
        });
    </script>
@endsection

@include('partials.headerfiles')
@include('partials.footerfiles')

@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')

    <main id="main" class="main">
        @include('partials.messages')

        <div class="pagetitle">
            <h1>{{ __('messages.manage_pdf') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.manage_pdf') }}</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Document Information</h5>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-box bg-light p-3 rounded">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-file-earmark-pdf text-danger fs-1 me-3"></i>
                                            <div>
                                                <h6 class="mb-0 text-muted">{{ __('messages.current_document_status') }}</h6>
                                                @if ($pdfExists)
                                                    <span class="badge bg-success fs-6 mt-1">{{ __('messages.active') }}</span>
                                                @else
                                                    <span class="badge bg-danger fs-6 mt-1">{{ __('messages.not_found') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-4">
                                                <small class="text-muted d-block">Document ID</small>
                                                <span class="badge bg-secondary font-monospace">{{ $documentId ?? 'N/A' }}</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">{{ __('messages.file_size') }}</small>
                                                <strong>{{ $pdfSize }}</strong>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">{{ __('messages.last_updated') }}</small>
                                                <strong>{{ $pdfModified }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="card-title mt-2">{{ __('messages.upload_new_pdf') }}</h5>
                            <p class="text-muted mb-4">
                                {{ __('messages.upload_pdf_desc') }}
                            </p>

                            <form id="pdfUploadForm" action="{{ route('admin.pdf.upload') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-3">
                                    <label for="pdfFile" class="col-sm-2 col-form-label">{{ __('messages.select_pdf_file') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-upload"></i></span>
                                            <input class="form-control" type="file" id="pdfFile" name="file"
                                                accept="application/pdf" required>
                                        </div>
                                        <div class="form-text mt-2 text-muted">
                                            <i class="bi bi-info-circle me-1"></i> {{ __('messages.max_file_size') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-10 offset-sm-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-cloud-upload me-1"></i> {{ __('messages.upload_process') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    @include('partials.footer')
@endsection
