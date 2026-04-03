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

        .remove-option-btn {
            color: #dc3545;
            cursor: pointer;
        }

        .remove-option-btn:hover {
            color: #a71d2a;
        }

        .question-type-settings {
            display: none;
        }

        .question-type-settings.active {
            display: block;
        }
    </style>
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        let optionIndex = {{ isset($data) && $data->options ? $data->options->count() : 0 }};

        $(document).ready(function() {
            // Show/hide settings based on question type
            toggleQuestionTypeSettings();

            $('#question_type').on('change', function() {
                toggleQuestionTypeSettings();
            });
        });

        function toggleQuestionTypeSettings() {
            var type = $('#question_type').val();

            // Hide all settings sections
            $('.question-type-settings').removeClass('active');

            // Show relevant section
            if (type === 'single_select' || type === 'multi_select') {
                $('#options-section').addClass('active');
            }
            if (type === 'multi_select') {
                $('#multi-select-settings').addClass('active');
            }
            if (type === 'rating_scale') {
                $('#rating-settings').addClass('active');
                $('#options-section').addClass('active'); // For rating row labels
            }
            if (type === 'text_input') {
                $('#text-settings').addClass('active');
            }
        }

        function addOption() {
            var html = `
                <div class="option-row" id="option-row-${optionIndex}">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">🇬🇧 {{ __('messages.option_text') }}</label>
                            <input type="text" name="options[${optionIndex}][option_text_en]" 
                                   class="form-control" placeholder="Option in English">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">🇫🇷 {{ __('messages.option_text') }}</label>
                            <input type="text" name="options[${optionIndex}][option_text_fr]" 
                                   class="form-control" placeholder="Option en français">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(${optionIndex})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">{{ __('messages.option_subtitle') }} (EN)</label>
                            <input type="text" name="options[${optionIndex}][option_subtitle_en]" 
                                   class="form-control form-control-sm" placeholder="Optional subtitle">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted">{{ __('messages.option_subtitle') }} (FR)</label>
                            <input type="text" name="options[${optionIndex}][option_subtitle_fr]" 
                                   class="form-control form-control-sm" placeholder="Sous-titre optionnel">
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
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ isset($data) ? __('messages.edit_question') : __('messages.add_question') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('managesections') }}">{{ __('messages.sections') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('managequestions', ['section_id' => $section->id]) }}">{{ $section->title_en }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ isset($data) ? __('messages.edit_question') : __('messages.add_question') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-11">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ isset($data) ? __('messages.edit_question') : __('messages.add_question') }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('storequestion') }}" method="POST"
                                novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">
                                <input type="hidden" name="section_id" value="{{ $section->id }}">

                                {{-- Question Type --}}
                                <div class="col-md-6 position-relative">
                                    <label for="question_type" class="form-label">{{ __('messages.question_type') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="question_type" id="question_type"
                                        class="form-select {{ $errors->has('question_type') ? 'is-invalid' : '' }}">
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
                                <div class="col-md-3 position-relative">
                                    <label for="order" class="form-label">{{ __('messages.question_order') }}</label>
                                    <input type="number" name="order" class="form-control" id="order"
                                        value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                        min="1">
                                </div>

                                {{-- Required & Active --}}
                                <div class="col-md-3 position-relative">
                                    <label class="form-label">{{ __('messages.status') }}</label>
                                    <div class="d-flex gap-3 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_required"
                                                id="is_required" {{ isset($data) && $data->is_required ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_required">{{ __('messages.question_required') }}</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                                {{ (isset($data) && $data->is_active) || !isset($data) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_active">{{ __('messages.active') }}</label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Question Text - Bilingual --}}
                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">{{ __('messages.question_text') }} <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="question_text_en" class="form-label">🇬🇧
                                        {{ __('messages.english') }}</label>
                                    <textarea name="question_text_en" id="question_text_en" rows="2"
                                        class="form-control {{ $errors->has('question_text_en') ? 'is-invalid' : '' }}"
                                        placeholder="Enter question in English">{{ isset($data) ? $data->question_text_en : old('question_text_en') }}</textarea>
                                    @if ($errors->has('question_text_en'))
                                        <div class="invalid-feedback">{{ $errors->first('question_text_en') }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="question_text_fr" class="form-label">🇫🇷
                                        {{ __('messages.french') }}</label>
                                    <textarea name="question_text_fr" id="question_text_fr" rows="2"
                                        class="form-control {{ $errors->has('question_text_fr') ? 'is-invalid' : '' }}"
                                        placeholder="Saisir la question en français">{{ isset($data) ? $data->question_text_fr : old('question_text_fr') }}</textarea>
                                    @if ($errors->has('question_text_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('question_text_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Helper Text - Bilingual --}}
                                <div class="col-12 mt-3">
                                    <label class="form-label fw-bold">{{ __('messages.question_helper') }}</label>
                                    <small class="text-muted">(e.g., "Multiple selection possible", "Up to 3
                                        priorities")</small>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="helper_text_en" class="form-label">🇬🇧
                                        {{ __('messages.english') }}</label>
                                    <input type="text" name="helper_text_en" id="helper_text_en" class="form-control"
                                        value="{{ isset($data) ? $data->helper_text_en : old('helper_text_en') }}"
                                        placeholder="Optional helper text">
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="helper_text_fr" class="form-label">🇫🇷 {{ __('messages.french') }}</label>
                                    <input type="text" name="helper_text_fr" id="helper_text_fr" class="form-control"
                                        value="{{ isset($data) ? $data->helper_text_fr : old('helper_text_fr') }}"
                                        placeholder="Texte d'aide optionnel">
                                </div>

                                {{-- Multi-Select Settings --}}
                                <div id="multi-select-settings"
                                    class="col-12 mt-4 question-type-settings {{ isset($data) && $data->question_type == 'multi_select' ? 'active' : '' }}">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ __('messages.multi_select') }}
                                                {{ __('messages.settings') }}</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="max_selections"
                                                        class="form-label">{{ __('messages.max_selections') }}</label>
                                                    <input type="number" name="max_selections" id="max_selections"
                                                        class="form-control" min="1"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['max_selections'] ?? '' : '' }}"
                                                        placeholder="Leave empty for unlimited">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rating Scale Settings --}}
                                <div id="rating-settings"
                                    class="col-12 mt-4 question-type-settings {{ isset($data) && $data->question_type == 'rating_scale' ? 'active' : '' }}">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ __('messages.rating_scale') }}
                                                {{ __('messages.settings') }}</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="min_value"
                                                        class="form-label">{{ __('messages.min_value') }}</label>
                                                    <input type="number" name="min_value" id="min_value"
                                                        class="form-control"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['min_value'] ?? 1 : 1 }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="max_value"
                                                        class="form-label">{{ __('messages.max_value') }}</label>
                                                    <input type="number" name="max_value" id="max_value"
                                                        class="form-control"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['max_value'] ?? 5 : 5 }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Text Input Settings --}}
                                <div id="text-settings"
                                    class="col-12 mt-4 question-type-settings {{ isset($data) && $data->question_type == 'text_input' ? 'active' : '' }}">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ __('messages.text_input') }}
                                                {{ __('messages.settings') }}</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="min_characters"
                                                        class="form-label">{{ __('messages.min_characters') }}</label>
                                                    <input type="number" name="min_characters" id="min_characters"
                                                        class="form-control" min="0"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['min_characters'] ?? '' : '' }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="max_characters"
                                                        class="form-label">{{ __('messages.max_characters') }}</label>
                                                    <input type="number" name="max_characters" id="max_characters"
                                                        class="form-control" min="1"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['max_characters'] ?? '' : '' }}">
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <label for="placeholder_en" class="form-label">🇬🇧
                                                        {{ __('messages.placeholder_text') }}</label>
                                                    <input type="text" name="placeholder_en" id="placeholder_en"
                                                        class="form-control"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['placeholder_en'] ?? '' : '' }}"
                                                        placeholder="e.g., Enter your expectations...">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="placeholder_fr" class="form-label">🇫🇷
                                                        {{ __('messages.placeholder_text') }}</label>
                                                    <input type="text" name="placeholder_fr" id="placeholder_fr"
                                                        class="form-control"
                                                        value="{{ isset($data) && $data->settings ? $data->settings['placeholder_fr'] ?? '' : '' }}"
                                                        placeholder="ex: Saisissez vos attentes...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Options Section (for select and rating types) --}}
                                <div id="options-section"
                                    class="col-12 mt-4 question-type-settings {{ isset($data) && in_array($data->question_type, ['single_select', 'multi_select', 'rating_scale']) ? 'active' : '' }}">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="card-title mb-0">{{ __('messages.options') }}</h6>
                                                <button type="button" class="btn btn-sm btn-success"
                                                    onclick="addOption()">
                                                    <i class="bi bi-plus"></i> {{ __('messages.add_option') }}
                                                </button>
                                            </div>
                                            <div id="options-container">
                                                @if (isset($data) && $data->options)
                                                    @foreach ($data->options as $index => $option)
                                                        <div class="option-row" id="option-row-{{ $index }}">
                                                            <div class="row">
                                                                <div class="col-md-5">
                                                                    <label class="form-label">🇬🇧
                                                                        {{ __('messages.option_text') }}</label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_text_en]"
                                                                        class="form-control"
                                                                        value="{{ $option->option_text_en }}">
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <label class="form-label">🇫🇷
                                                                        {{ __('messages.option_text') }}</label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_text_fr]"
                                                                        class="form-control"
                                                                        value="{{ $option->option_text_fr }}">
                                                                </div>
                                                                <div class="col-md-2 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger"
                                                                        onclick="removeOption({{ $index }})">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-2">
                                                                <div class="col-md-5">
                                                                    <label
                                                                        class="form-label small text-muted">{{ __('messages.option_subtitle') }}
                                                                        (EN)
                                                                    </label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_subtitle_en]"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $option->option_subtitle_en }}">
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <label
                                                                        class="form-label small text-muted">{{ __('messages.option_subtitle') }}
                                                                        (FR)</label>
                                                                    <input type="text"
                                                                        name="options[{{ $index }}][option_subtitle_fr]"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $option->option_subtitle_fr }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <div class="text-muted small mt-2">
                                                <i class="bi bi-info-circle"></i>
                                                {{ __('messages.question_type') == 'rating_scale' ? 'For rating scale, options are used as row labels (e.g., Relational, Analytical, etc.)' : 'Add the answer options that users can select from.' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ isset($data) ? __('messages.save') : __('messages.add') }}
                                    </button>
                                    <a href="{{ route('managequestions', ['section_id' => $section->id]) }}"
                                        class="btn btn-secondary">
                                        {{ __('messages.cancel') }}
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
