@extends('layouts.business')

@section('title', 'Add ' . __('messages.business_policies') . ' Question')

@section('pagewisestyle')
    <style>
        .option-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
    </style>
@endsection

@section('customjs')
    <script type="text/javascript">
        let optionIndex = 0;

        function addOption() {
            var html = `
                <div class="option-row" id="option-row-${optionIndex}">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">🇬🇧 Option (EN) <span class="text-danger">*</span></label>
                            <input type="text" name="options[${optionIndex}][en]" 
                                   class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">🇫🇷 Option (FR) <span class="text-danger">*</span></label>
                            <input type="text" name="options[${optionIndex}][fr]" 
                                   class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end pt-3">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(${optionIndex})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#options-container').append(html);
            optionIndex++;
        }

        function removeOption(index) {
            $('#option-row-' + index).remove();
        }

        $(document).ready(function() {
            addOption();
            addOption();
        });
    </script>
@endsection

@section('content')
    <div class="pagetitle">
        <h1>Add Policy Question</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('business.policies.index') }}">{{ __('messages.business_policies') }}</a></li>
                <li class="breadcrumb-item active">Add Question</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-11">
                @include('partials.messages')

                <div class="card shadow-sm">
                    <div class="card-body pt-3">
                        <h5 class="card-title mb-3">Add {{ __('messages.business_policies') }} Question</h5>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form class="row g-3" action="{{ route('business.policies.store') }}" method="POST">
                            @csrf

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">🇬🇧 Section Name (EN) <span class="text-danger">*</span></label>
                                <input type="text" name="section_name_en" class="form-control" value="{{ old('section_name_en') }}" 
                                    placeholder="e.g. Risk Assessment, Loan Eligibility" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">🇫🇷 Section Name (FR) <span class="text-danger">*</span></label>
                                <input type="text" name="section_name_fr" class="form-control" value="{{ old('section_name_fr') }}" 
                                    placeholder="e.g. Évaluation des risques, Éligibilité au prêt" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">🇬🇧 Question (EN) <span class="text-danger">*</span></label>
                                <textarea name="question_en" rows="3" class="form-control" 
                                    placeholder="Enter question in English..." required>{{ old('question_en') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">🇫🇷 Question (FR) <span class="text-danger">*</span></label>
                                <textarea name="question_fr" rows="3" class="form-control" 
                                    placeholder="Enter question in French..." required>{{ old('question_fr') }}</textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3 pt-3">
                                            <h6 class="card-title mb-0 m-0 pb-0">Answers / Evaluation Options</h6>
                                            <button type="button" class="btn btn-sm btn-success" onclick="addOption()">
                                                <i class="bi bi-plus-circle me-1"></i> Add Option
                                            </button>
                                        </div>
                                        <div id="options-container">
                                            <!-- Dynamic options here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i> Save Policy Question
                                </button>
                                <a href="{{ route('business.policies.index') }}" class="btn btn-secondary px-3">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
