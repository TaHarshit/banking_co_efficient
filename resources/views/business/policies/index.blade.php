@extends('layouts.business')

@section('title', __('messages.business_policies'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.business_policies') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.business_policies') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <!-- Card 1: Internal Business Policies Message -->
                <div class="card shadow-sm mb-4 border-start border-primary border-4">
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title m-0 p-0 text-primary">
                                <i class="bi bi-chat-square-quote me-2"></i>{{ __('messages.internal_business_policies_message') }}
                            </h5>
                            <span class="badge bg-light text-secondary border">Optional</span>
                        </div>
                        <p class="text-muted small mb-3">
                            {{ __('messages.business_policies_message_hint') }}
                        </p>

                        <form action="{{ route('business.policies.message.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <textarea name="business_policies_message" class="form-control" rows="4" 
                                    placeholder="Enter your bank's internal policies, compliance notice, or instructions to be displayed when users create a new case study...">{{ old('business_policies_message', $business->business_policies_message) }}</textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    @if ($business->business_policies_message)
                                        <i class="bi bi-check-circle text-success me-1"></i> Policy message is active for your bank.
                                    @else
                                        <i class="bi bi-info-circle me-1"></i> No message configured. Users will see standard case creation flow without this prompt.
                                    @endif
                                </small>
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-save me-1"></i> {{ __('messages.save_message') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card 2: Policy Questions -->
                <div class="card shadow-sm">
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="card-title m-0 p-0">
                                    <i class="bi bi-card-checklist me-2"></i>Policy Questions & Criteria
                                </h5>
                                <p class="text-muted small m-0">Questions and evaluation criteria used during case study analysis.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-upload me-1"></i> {{ __('messages.import') }}
                                </button>
                                <a href="{{ route('business.policies.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> {{ __('messages.add_question') }}
                                </a>
                            </div>
                        </div>

                        @if (!$hasCustomQuestions)
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2 fs-5"></i>
                                <div>
                                    <strong>Default Global Policies Active:</strong> Your bank currently uses the system's global default policy questions ({{ $globalQuestionsCount }} questions). 
                                    Once you add or import customized questions here, your bank's case studies will automatically evaluate against your specific policies.
                                </div>
                            </div>
                        @endif

                        @if ($questions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('messages.section') }} (EN)</th>
                                            <th>{{ __('messages.section') }} (FR)</th>
                                            <th>{{ __('messages.question') }} (EN)</th>
                                            <th>{{ __('messages.question') }} (FR)</th>
                                            <th class="text-center">{{ __('messages.options') }}</th>
                                            <th class="text-center" width="130">{{ __('messages.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($questions as $question)
                                            <tr>
                                                <td><span class="fw-semibold">{{ $question->section_name_en ?: $question->section_name }}</span></td>
                                                <td><span class="fw-semibold">{{ $question->section_name_fr ?: $question->section_name }}</span></td>
                                                <td>{{ Str::limit($question->question_en, 50) }}</td>
                                                <td>{{ Str::limit($question->question_fr, 50) }}</td>
                                                <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $question->options->count() }}</span></td>
                                                <td class="text-center">
                                                    <a href="{{ route('business.policies.edit', $question->id) }}"
                                                        class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                    <a href="{{ route('business.policies.destroy', $question->id) }}"
                                                        onclick="return confirm('{{ __('messages.confirm_delete_policy_question') }}')"
                                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $questions->links() }}
                            </div>
                        @else
                            <div class="text-center py-4 border rounded bg-light">
                                <i class="bi bi-shield-lock display-6 text-muted mb-2 d-block"></i>
                                <h6 class="text-muted">No custom policy questions yet.</h6>
                                <p class="text-muted small mb-3">Click "Add Question" to create your bank's specific policy questions, or "Import" from an Excel/CSV file.</p>
                                <a href="{{ route('business.policies.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Policy Question
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('business.policies.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('messages.import') }} {{ __('messages.business_policies') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Upload an Excel (.xlsx, .xls) or CSV file containing your bank's policy questions and options.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-upload me-1"></i> Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
