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

        .option-row:hover {
            border-color: #dee2e6;
        }

        .option-row.correct-answer {
            background: #d4edda;
            border-color: #28a745;
        }

        .question-type-settings {
            display: none;
        }

        .question-type-settings.active {
            display: block;
        }

        .correct-answer-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        let optionIndex = {{ isset($data) && $data->options ? $data->options->count() : 0 }};

        $(document).ready(function() {
            toggleQuestionTypeSettings();
            $('#question_type').on('change', function() {
                toggleQuestionTypeSettings();
            });
        });

        function toggleQuestionTypeSettings() {
            var type = $('#question_type').val();
            $('.question-type-settings').removeClass('active');
            if (type === 'radio' || type === 'multi_select') {
                $('#options-section').addClass('active');
            }
            // Show/hide correct answer column based on type
            if (type === 'radio') {
                $('.correct-answer-col').show();
                $('.correct-answer-info-radio').show();
                $('.correct-answer-info-multi').hide();
            } else if (type === 'multi_select') {
                $('.correct-answer-col').show();
                $('.correct-answer-info-radio').hide();
                $('.correct-answer-info-multi').show();
            } else {
                $('.correct-answer-col').hide();
            }
        }

        function addOption() {
            var questionType = $('#question_type').val();
            var inputType = questionType === 'radio' ? 'radio' : 'checkbox';
            var inputName = questionType === 'radio' ? 'correct_answer' : `options[${optionIndex}][is_correct]`;

            var html = `
                <div class="option-row" id="option-row-${optionIndex}">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.option_text_en') ?? 'Option Text (EN)' }} <span class="text-danger">*</span></label>
                            <input type="text" name="options[${optionIndex}][option_text]" 
                                   class="form-control" placeholder="{{ __('messages.enter_option_text') ?? 'Enter option text' }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.option_text_fr') ?? 'Option Text (FR)' }}</label>
                            <input type="text" name="options[${optionIndex}][option_text_fr]" 
                                   class="form-control" placeholder="Entrer le texte de l'option">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.weightage') ?? 'Weightage' }} <span class="text-danger">*</span></label>
                            <input type="number" name="options[${optionIndex}][weightage]" 
                                   class="form-control" step="0.01" min="0" max="100" value="0" required>
                        </div>
                        <div class="col-md-2 correct-answer-col">
                            <label class="form-label">{{ __('messages.correct_answer') ?? 'Correct' }}</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="${inputType}" 
                                       name="${inputName}" 
                                       value="${optionIndex}"
                                       onchange="highlightCorrectAnswer(this)">
                                <label class="form-check-label">{{ __('messages.correct') ?? 'Correct' }}</label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(${optionIndex})">
                                <i class="bi bi-trash"></i> {{ __('messages.remove') ?? 'Remove' }}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#options-container').append(html);
            optionIndex++;
            toggleQuestionTypeSettings();
        }

        function removeOption(index) {
            $('#option-row-' + index).remove();
        }

        function highlightCorrectAnswer(el) {
            var questionType = $('#question_type').val();
            if (questionType === 'radio') {
                // Remove highlight from all options
                $('.option-row').removeClass('correct-answer');
                // Add highlight to selected option
                if (el.checked) {
                    $(el).closest('.option-row').addClass('correct-answer');
                }
            } else {
                // For multi-select, toggle the individual option
                if (el.checked) {
                    $(el).closest('.option-row').addClass('correct-answer');
                } else {
                    $(el).closest('.option-row').removeClass('correct-answer');
                }
            }
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
            <h1>{{ isset($data) ? __('messages.edit_question') ?? 'Edit Question' : __('messages.add_question') ?? 'Add Question' }}
            </h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</li>
                    @if (isset($examTemplate) && $examTemplate)
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.exams') ?? 'Exams' }}</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentsections', ['exam_template_id' => $examTemplate->id]) }}">{{ $examTemplate->title }}</a>
                        </li>
                    @endif
                    <li class="breadcrumb-item"><a
                            href="{{ route('manageskillassessmentquestions', array_filter(['exam_template_id' => $examTemplateId ?? null])) }}">{{ __('messages.questions') ?? 'Questions' }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ isset($data) ? __('messages.edit_question') ?? 'Edit Question' : __('messages.add_question') ?? 'Add Question' }}
                    </li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ isset($data) ? __('messages.edit_question') ?? 'Edit Question' : __('messages.add_question') ?? 'Add Question' }}
                            </h5>
                            <form class="row g-3 needs-validation" action="{{ route('storeskillassessmentquestion') }}"
                                method="POST" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                                {{-- Section Dropdown --}}
                                <div class="col-md-4 position-relative">
                                    <label for="skill_assessment_section_id"
                                        class="form-label">{{ __('messages.section') ?? 'Section' }} <span
                                            class="text-danger">*</span></label>
                                    <select name="skill_assessment_section_id" id="skill_assessment_section_id"
                                        class="form-select {{ $errors->has('skill_assessment_section_id') ? 'is-invalid' : '' }}"
                                        required>
                                        <option value="">{{ __('messages.select_section') ?? 'Select Section' }}
                                        </option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
                                                {{ (isset($data) && $data->skill_assessment_section_id == $section->id) || (isset($sectionId) && $sectionId == $section->id) ? 'selected' : '' }}>
                                                {{ $section->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('skill_assessment_section_id'))
                                        <div class="invalid-feedback">{{ $errors->first('skill_assessment_section_id') }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Question Type --}}
                                <div class="col-md-4 position-relative">
                                    <label for="question_type"
                                        class="form-label">{{ __('messages.question_type') ?? 'Question Type' }} <span
                                            class="text-danger">*</span></label>
                                    <select name="question_type" id="question_type"
                                        class="form-select {{ $errors->has('question_type') ? 'is-invalid' : '' }}"
                                        required>
                                        @foreach ($questionTypes as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ isset($data) && $data->question_type == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('question_type'))
                                        <div class="invalid-feedback">{{ $errors->first('question_type') }}</div>
                                    @endif
                                </div>

                                {{-- Order --}}
                                <div class="col-md-2 position-relative">
                                    <label for="order" class="form-label">{{ __('messages.order') ?? 'Order' }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="order"
                                        class="form-control {{ $errors->has('order') ? 'is-invalid' : '' }}" id="order"
                                        value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                        min="1" required>
                                    @if ($errors->has('order'))
                                        <div class="invalid-feedback">{{ $errors->first('order') }}</div>
                                    @endif
                                </div>

                                {{-- Required & Active --}}
                                <div class="col-md-2 position-relative">
                                    <label class="form-label">{{ __('messages.settings') ?? 'Settings' }}</label>
                                    <div class="d-flex flex-column gap-1 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_required"
                                                id="is_required" {{ isset($data) && $data->is_required ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_required">{{ __('messages.required') ?? 'Required' }}</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                                {{ (isset($data) && $data->is_active) || !isset($data) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_active">{{ __('messages.active') ?? 'Active' }}</label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Question Text EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="question_text"
                                        class="form-label fw-bold">{{ __('messages.question_text_en') ?? 'Question Text (EN)' }}
                                        <span class="text-danger">*</span></label>
                                    <textarea name="question_text" id="question_text" rows="3"
                                        class="form-control {{ $errors->has('question_text') ? 'is-invalid' : '' }}"
                                        placeholder="{{ __('messages.enter_question') ?? 'Enter your question here...' }}" required>{{ isset($data) ? $data->question_text : old('question_text') }}</textarea>
                                    @if ($errors->has('question_text'))
                                        <div class="invalid-feedback">{{ $errors->first('question_text') }}</div>
                                    @endif
                                </div>

                                {{-- Question Text FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="question_text_fr"
                                        class="form-label fw-bold">{{ __('messages.question_text_fr') ?? 'Question Text (FR)' }}</label>
                                    <textarea name="question_text_fr" id="question_text_fr" rows="3"
                                        class="form-control {{ $errors->has('question_text_fr') ? 'is-invalid' : '' }}"
                                        placeholder="Entrez votre question ici...">{{ isset($data) ? $data->question_text_fr : old('question_text_fr') }}</textarea>
                                    @if ($errors->has('question_text_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('question_text_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Helper Text EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="helper_text"
                                        class="form-label">{{ __('messages.helper_text_en') ?? 'Helper Text (EN)' }}</label>
                                    <input type="text" name="helper_text" id="helper_text" class="form-control"
                                        value="{{ isset($data) ? $data->helper_text : old('helper_text') }}"
                                        placeholder="{{ __('messages.optional_helper') ?? 'Optional helper text for the user' }}">
                                </div>

                                {{-- Helper Text FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="helper_text_fr"
                                        class="form-label">{{ __('messages.helper_text_fr') ?? 'Helper Text (FR)' }}</label>
                                    <input type="text" name="helper_text_fr" id="helper_text_fr" class="form-control"
                                        value="{{ isset($data) ? $data->helper_text_fr : old('helper_text_fr') }}"
                                        placeholder="Texte d'aide facultatif pour l'utilisateur">
                                </div>

                                {{-- Options Section (for radio and multi_select types) --}}
                                <div id="options-section"
                                    class="col-12 mt-4 question-type-settings {{ isset($data) && in_array($data->question_type, ['radio', 'multi_select']) ? 'active' : (!isset($data) ? 'active' : '') }}">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="card-title mb-0">
                                                    {{ __('messages.options') ?? 'Answer Options' }}</h6>
                                                <button type="button" class="btn btn-sm btn-success"
                                                    onclick="addOption()">
                                                    <i class="bi bi-plus"></i>
                                                    {{ __('messages.add_option') ?? 'Add Option' }}
                                                </button>
                                            </div>
                                            <div id="options-container">
                                                @if (isset($data) && $data->options)
                                                    @foreach ($data->options as $index => $option)
                                                        <div class="option-row {{ $option->is_correct ? 'correct-answer' : '' }}"
                                                            id="option-row-{{ $index }}">
                                                            <div class="row align-items-end">
                                                                <div class="col-md-3">
                                                                    <label
                                                                        class="form-label">{{ __('messages.option_text_en') ?? 'Option Text (EN)' }}
                                                                        <span class="text-danger">*</span></label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_text]"
                                                                        class="form-control"
                                                                        value="{{ $option->option_text }}" required>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label
                                                                        class="form-label">{{ __('messages.option_text_fr') ?? 'Option Text (FR)' }}</label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_text_fr]"
                                                                        class="form-control"
                                                                        value="{{ $option->option_text_fr }}">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label
                                                                        class="form-label">{{ __('messages.weightage') ?? 'Weightage' }}
                                                                        <span class="text-danger">*</span></label>
                                                                    <input type="number"
                                                                        name="options[{{ $index }}][weightage]"
                                                                        class="form-control" step="0.01"
                                                                        min="0" max="100"
                                                                        value="{{ $option->weightage }}" required>
                                                                </div>
                                                                <div class="col-md-2 correct-answer-col">
                                                                    <label
                                                                        class="form-label">{{ __('messages.correct_answer') ?? 'Correct' }}</label>
                                                                    <div class="form-check mt-2">
                                                                        @if (isset($data) && $data->question_type == 'radio')
                                                                            <input class="form-check-input" type="radio"
                                                                                name="correct_answer"
                                                                                value="{{ $index }}"
                                                                                {{ $option->is_correct ? 'checked' : '' }}
                                                                                onchange="highlightCorrectAnswer(this)">
                                                                        @else
                                                                            <input class="form-check-input"
                                                                                type="checkbox"
                                                                                name="options[{{ $index }}][is_correct]"
                                                                                value="1"
                                                                                {{ $option->is_correct ? 'checked' : '' }}
                                                                                onchange="highlightCorrectAnswer(this)">
                                                                        @endif
                                                                        <label
                                                                            class="form-check-label">{{ __('messages.correct') ?? 'Correct' }}</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger"
                                                                        onclick="removeOption({{ $index }})">
                                                                        <i class="bi bi-trash"></i>
                                                                        {{ __('messages.remove') ?? 'Remove' }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <div class="alert alert-info mt-3 correct-answer-info-radio"
                                                style="{{ isset($data) && $data->question_type == 'multi_select' ? 'display:none' : '' }}">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>{{ __('messages.radio_info') ?? 'Radio (Single Select)' }}:</strong>
                                                {{ __('messages.radio_info_desc') ?? 'Select ONE correct answer. The selected option\'s weightage becomes the score.' }}
                                            </div>
                                            <div class="alert alert-warning mt-3 correct-answer-info-multi"
                                                style="{{ !isset($data) || $data->question_type != 'multi_select' ? 'display:none' : '' }}">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>{{ __('messages.multi_info') ?? 'Multi Select' }}:</strong>
                                                {{ __('messages.multi_info_desc') ?? 'Select ALL correct answers. The sum of selected options\' weightages becomes the score.' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ isset($data) ? __('messages.save') ?? 'Save' : __('messages.add') ?? 'Add' }}
                                    </button>
                                    <a href="{{ route('manageskillassessmentquestions', array_filter(['exam_template_id' => $examTemplateId ?? null, 'section_id' => isset($data) ? $data->skill_assessment_section_id : $sectionId ?? null])) }}"
                                        class="btn btn-secondary">
                                        {{ __('messages.cancel') ?? 'Cancel' }}
                                    </a>
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
