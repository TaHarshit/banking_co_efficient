@extends('layouts.app')

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
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">🇬🇧 {{ __('messages.option_text_en') }}</label>
                            <input type="text" name="options[${optionIndex}][en]" 
                                   class="form-control" 
                                   placeholder="{{ __('messages.placeholder_option_en') }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">🇫🇷 {{ __('messages.option_text_fr') }}</label>
                            <input type="text" name="options[${optionIndex}][fr]" 
                                   class="form-control" 
                                   placeholder="{{ __('messages.placeholder_option_fr') }}" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(${optionIndex})">
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
            // Add initial options
            addOption();
            addOption();
        });
    </script>
@endsection

@include('partials.headerfiles')
@include('partials.footerfiles')

@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.add_policy_question') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.case_study_questions.index') }}">{{ __('messages.business_policies') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.add_question') }}</li>
                </ol>
            </nav>
        </div>
        
        <section class="section">
            <div class="row">
                <div class="col-lg-11">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.add_policy_question') }}</h5>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form class="row g-3" action="{{ route('admin.case_study_questions.store') }}" method="POST">
                                @csrf
                                
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('messages.bank_or_business') }}</label>
                                    <select name="business_id" class="form-select">
                                        <option value="">{{ __('messages.global_default') }}</option>
                                        @foreach ($businesses as $b)
                                            <option value="{{ $b->id }}" {{ old('business_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('messages.assign_business_or_global') }}</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">🇬🇧 {{ __('messages.section_name_en') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="section_name_en" class="form-control" value="{{ old('section_name_en') }}" 
                                        placeholder="{{ __('messages.placeholder_section_name_en') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">🇫🇷 {{ __('messages.section_name_fr') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="section_name_fr" class="form-control" value="{{ old('section_name_fr') }}" 
                                        placeholder="{{ __('messages.placeholder_section_name_fr') }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">🇬🇧 {{ __('messages.question_en') }} <span class="text-danger">*</span></label>
                                    <textarea name="question_en" rows="3" class="form-control" 
                                        placeholder="{{ __('messages.placeholder_question_en') }}" required>{{ old('question_en') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">🇫🇷 {{ __('messages.question_fr') }} <span class="text-danger">*</span></label>
                                    <textarea name="question_fr" rows="3" class="form-control" 
                                        placeholder="{{ __('messages.placeholder_question_fr') }}" required>{{ old('question_fr') }}</textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3 pt-3">
                                                <h6 class="card-title mb-0 m-0 pb-0">{{ __('messages.options') }}</h6>
                                                <button type="button" class="btn btn-sm btn-success" onclick="addOption()">
                                                    <i class="bi bi-plus"></i> {{ __('messages.add_option') }}
                                                </button>
                                            </div>
                                            <div id="options-container">
                                                <!-- Dynamic options here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">{{ __('messages.save_policy_question') }}</button>
                                    <a href="{{ route('admin.case_study_questions.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    @include('partials.footer')
@endsection
