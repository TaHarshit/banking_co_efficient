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
                            <label class="form-label">🇬🇧 Option (EN)</label>
                            <input type="text" name="options[${optionIndex}][en]" 
                                   class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">🇫🇷 Option (FR)</label>
                            <input type="text" name="options[${optionIndex}][fr]" 
                                   class="form-control" required>
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
            // Add initial option
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
            <h1>Add Case Study Question</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.case_study_questions.index') }}">Case Study Questions</a></li>
                    <li class="breadcrumb-item active">Add Question</li>
                </ol>
            </nav>
        </div>
        
        <section class="section">
            <div class="row">
                <div class="col-lg-11">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Add Case Study Question</h5>

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
                                    <label class="form-label">Section Name <span class="text-danger">*</span></label>
                                    <input type="text" name="section_name" class="form-control" value="{{ old('section_name') }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">🇬🇧 Question (EN) <span class="text-danger">*</span></label>
                                    <textarea name="question_en" rows="3" class="form-control" required>{{ old('question_en') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">🇫🇷 Question (FR) <span class="text-danger">*</span></label>
                                    <textarea name="question_fr" rows="3" class="form-control" required>{{ old('question_fr') }}</textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3 pt-3">
                                                <h6 class="card-title mb-0 m-0 pb-0">Options</h6>
                                                <button type="button" class="btn btn-sm btn-success" onclick="addOption()">
                                                    <i class="bi bi-plus"></i> Add Option
                                                </button>
                                            </div>
                                            <div id="options-container">
                                                <!-- Dynamic options here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">Save Question</button>
                                    <a href="{{ route('admin.case_study_questions.index') }}" class="btn btn-secondary">Cancel</a>
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
