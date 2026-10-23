@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_question') : __('messages.add_question'))

@section('pagewisestyle')
    <style>
        tr.correct-answer-row,
        tr.correct-answer-row td {
            background-color: #d4edda !important;
        }
    </style>
@endsection

@section('content')
    <div class="pagetitle">
        <h1>{{ isset($data) ? __('messages.edit_question') ?? 'Edit Question' : __('messages.add_question') ?? 'Add Question' }}
        </h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.skill-assessment.sections') }}">{{ __('messages.sections') }}</a></li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.skill-assessment.questions', $section->id) }}">{{ __('messages.questions') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ isset($data) ? __('messages.edit') : __('messages.add') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            {{ isset($data) ? __('messages.edit_question') ?? 'Edit Question' : __('messages.add_question') ?? 'Add Question' }}
                        </h5>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>{{ __('messages.section') }}:</strong> {{ $section->title }}
                        </div>

                        <form action="{{ route('business.skill-assessment.questions.store', $section->id) }}"
                            method="POST" id="questionForm">
                            @csrf
                            <input type="hidden" name="id" value="{{ $data->id ?? 0 }}">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="question_type"
                                            class="form-label">{{ __('messages.question_type') ?? 'Question Type' }} <span
                                                class="text-danger">*</span></label>
                                        <select name="question_type" id="question_type"
                                            class="form-select @error('question_type') is-invalid @enderror" required>
                                            @foreach ($typeOptions as $key => $value)
                                                @if ($key === 'open_text' && (!isset($data) || ($data->question_type ?? '') !== 'open_text'))
                                                    @continue
                                                @endif
                                                <option value="{{ $key }}"
                                                    {{ old('question_type', $data->question_type ?? '') == $key ? 'selected' : '' }}>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @error('question_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="order" class="form-label">{{ __('messages.order') }}</label>
                                        <input type="number" name="order" id="order" class="form-control"
                                            value="{{ old('order', $data->order ?? ($nextOrder ?? 1)) }}" min="1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3 pt-4">
                                        <div class="form-check form-switch d-inline-block me-3">
                                            <input class="form-check-input" type="checkbox" name="is_required"
                                                id="is_required"
                                                {{ old('is_required', $data->is_required ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_required">{{ __('messages.required') }}</label>
                                        </div>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                                {{ old('is_active', $data->is_active ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_active">{{ __('messages.active') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="question_text"
                                        class="form-label">{{ __('messages.question_text_en') ?? 'Question Text (EN)' }} <span
                                            class="text-danger">*</span></label>
                                    <textarea name="question_text" id="question_text" class="form-control @error('question_text') is-invalid @enderror"
                                        rows="3" required>{{ old('question_text', isset($data) ? ($data->getRawOriginal('question_text') ?? $data->question_text) : '') }}</textarea>
                                    @error('question_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="question_text_fr"
                                        class="form-label">{{ __('messages.question_text_fr') ?? 'Question Text (FR)' }}</label>
                                    <textarea name="question_text_fr" id="question_text_fr" class="form-control @error('question_text_fr') is-invalid @enderror"
                                        rows="3" placeholder="Entrez la question en français...">{{ old('question_text_fr', $data->question_text_fr ?? '') }}</textarea>
                                    @error('question_text_fr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="helper_text"
                                        class="form-label">{{ __('messages.helper_text_en') ?? 'Helper Text (EN)' }}</label>
                                    <input type="text" name="helper_text" id="helper_text" class="form-control"
                                        value="{{ old('helper_text', isset($data) ? ($data->getRawOriginal('helper_text') ?? $data->helper_text) : '') }}"
                                        placeholder="{{ __('messages.optional_helper') ?? 'Optional helper text for the user' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="helper_text_fr"
                                        class="form-label">{{ __('messages.helper_text_fr') ?? 'Helper Text (FR)' }}</label>
                                    <input type="text" name="helper_text_fr" id="helper_text_fr" class="form-control"
                                        value="{{ old('helper_text_fr', $data->helper_text_fr ?? '') }}"
                                        placeholder="Texte d'aide facultatif en français">
                                </div>
                            </div>

                            <!-- Options Section (for radio and multi_select) -->
                            <div id="optionsSection" class="mb-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">{{ __('messages.options') ?? 'Options' }} <span
                                            class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                                        <i class="bi bi-plus-circle"></i> {{ __('messages.add_option') ?? 'Add Option' }}
                                    </button>
                                </div>

                                <table class="table table-bordered align-middle" id="optionsTable">
                                    <thead>
                                        <tr>
                                            <th width="50" class="text-center correct-col">{{ __('messages.correct') ?? 'Correct' }}</th>
                                            <th>{{ __('messages.option_text_en') ?? 'Option Text (EN)' }} <span class="text-danger">*</span></th>
                                            <th>{{ __('messages.option_text_fr') ?? 'Option Text (FR)' }}</th>
                                            <th width="120">{{ __('messages.weightage') ?? 'Weightage' }} <span class="text-danger">*</span></th>
                                            <th width="60" class="text-center">{{ __('messages.action') ?? 'Action' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="optionsBody">
                                        @if (isset($data) && $data->options->count() > 0)
                                            @php
                                                $selectedRadioIndex = null;
                                                if ($data->question_type === 'radio') {
                                                    foreach ($data->options as $idx => $opt) {
                                                        if ($opt->is_correct) {
                                                            $selectedRadioIndex = $idx;
                                                            break;
                                                        }
                                                    }
                                                    if ($selectedRadioIndex === null && $data->options->count() > 0) {
                                                        $selectedRadioIndex = 0;
                                                    }
                                                }
                                            @endphp
                                            @foreach ($data->options as $index => $option)
                                                @php
                                                    $isRadioChecked = ($data->question_type === 'radio' && $selectedRadioIndex === $index);
                                                @endphp
                                                <tr class="{{ $isRadioChecked ? 'correct-answer-row table-success' : '' }}">
                                                    <td class="text-center correct-col" style="{{ $data->question_type === 'radio' ? '' : 'display: none;' }}">
                                                        <input type="radio" name="correct_answer"
                                                            value="{{ $index }}"
                                                            class="form-check-input correct-radio"
                                                            {{ $isRadioChecked ? 'checked' : '' }}
                                                            {{ $data->question_type === 'radio' ? 'required' : 'disabled' }}>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="options[{{ $index }}][option_text]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $option->getRawOriginal('option_text') ?? $option->option_text }}"
                                                            placeholder="{{ __('messages.enter_option_text') ?? 'Option text (EN)' }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="options[{{ $index }}][option_text_fr]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $option->option_text_fr }}"
                                                            placeholder="Texte de l'option (FR)">
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="options[{{ $index }}][weightage]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $option->weightage }}" min="0" max="100"
                                                            step="0.01" required>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="removeOption(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr class="correct-answer-row table-success">
                                                <td class="text-center correct-col">
                                                    <input type="radio" name="correct_answer" value="0"
                                                        class="form-check-input correct-radio" checked required>
                                                </td>
                                                <td>
                                                    <input type="text" name="options[0][option_text]"
                                                        class="form-control form-control-sm"
                                                        placeholder="{{ __('messages.enter_option_text') ?? 'Option text (EN)' }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="options[0][option_text_fr]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Texte de l'option (FR)">
                                                </td>
                                                <td>
                                                    <input type="number" name="options[0][weightage]"
                                                        class="form-control form-control-sm" value="0"
                                                        min="0" max="100" step="0.01" required>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="removeOption(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center correct-col">
                                                    <input type="radio" name="correct_answer" value="1"
                                                        class="form-check-input correct-radio" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="options[1][option_text]"
                                                        class="form-control form-control-sm"
                                                        placeholder="{{ __('messages.enter_option_text') ?? 'Option text (EN)' }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="options[1][option_text_fr]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Texte de l'option (FR)">
                                                </td>
                                                <td>
                                                    <input type="number" name="options[1][weightage]"
                                                        class="form-control form-control-sm" value="0"
                                                        min="0" max="100" step="0.01" required>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="removeOption(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i>
                                    {{ isset($data) ? __('messages.update') : __('messages.save') }}
                                </button>
                                <a href="{{ route('business.skill-assessment.questions', $section->id) }}"
                                    class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> {{ __('messages.cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('customjs')
    <script>
        let optionIndex = {{ isset($data) && $data->options->count() > 0 ? $data->options->count() : 2 }};

        function updateCorrectHighlight() {
            const type = document.getElementById('question_type').value;
            const rows = document.querySelectorAll('#optionsBody tr');
            rows.forEach(row => {
                row.classList.remove('correct-answer-row', 'table-success');
            });

            if (type === 'radio') {
                const checkedRadio = document.querySelector('input[name="correct_answer"]:checked');
                if (checkedRadio) {
                    const tr = checkedRadio.closest('tr');
                    if (tr) {
                        tr.classList.add('correct-answer-row', 'table-success');
                    }
                }
            }
        }

        function toggleOptionsSection() {
            const type = document.getElementById('question_type').value;
            const optionsSection = document.getElementById('optionsSection');
            const correctCols = document.querySelectorAll('.correct-col');
            const correctRadios = document.querySelectorAll('input[name="correct_answer"]');

            if (type === 'radio' || type === 'multi_select') {
                optionsSection.style.display = 'block';

                if (type === 'radio') {
                    correctCols.forEach(col => col.style.display = '');
                    correctRadios.forEach(r => {
                        r.disabled = false;
                        r.required = true;
                    });
                    const checkedRadio = document.querySelector('input[name="correct_answer"]:checked');
                    if (!checkedRadio && correctRadios.length > 0) {
                        correctRadios[0].checked = true;
                    }
                    updateCorrectHighlight();
                } else {
                    // Multi-select: hide correct column, disable radios, remove highlights
                    correctCols.forEach(col => col.style.display = 'none');
                    correctRadios.forEach(r => {
                        r.disabled = true;
                        r.required = false;
                        r.checked = false;
                    });
                    updateCorrectHighlight();
                }
            } else {
                optionsSection.style.display = 'none';
                correctRadios.forEach(r => {
                    r.disabled = true;
                    r.required = false;
                });
            }
        }

        function addOption() {
            const type = document.getElementById('question_type').value;
            const isRadio = type === 'radio';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center correct-col" ${isRadio ? '' : 'style="display: none;"'}>
                    <input type="radio" name="correct_answer" value="${optionIndex}" class="form-check-input correct-radio" ${isRadio ? 'required' : 'disabled'}>
                </td>
                <td>
                    <input type="text" name="options[${optionIndex}][option_text]" class="form-control form-control-sm" placeholder="{{ __('messages.enter_option_text') ?? 'Option text (EN)' }}" required>
                </td>
                <td>
                    <input type="text" name="options[${optionIndex}][option_text_fr]" class="form-control form-control-sm" placeholder="Texte de l'option (FR)">
                </td>
                <td>
                    <input type="number" name="options[${optionIndex}][weightage]" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            document.getElementById('optionsBody').appendChild(row);

            if (isRadio) {
                const checkedRadio = document.querySelector('input[name="correct_answer"]:checked');
                if (!checkedRadio) {
                    row.querySelector('input[name="correct_answer"]').checked = true;
                }
            }
            updateCorrectHighlight();
            optionIndex++;
        }

        function removeOption(btn) {
            const tbody = document.getElementById('optionsBody');
            if (tbody.rows.length > 2) {
                const row = btn.closest('tr');
                const wasChecked = row.querySelector('input[name="correct_answer"]:checked');
                row.remove();
                if (wasChecked) {
                    const firstRadio = tbody.querySelector('input[name="correct_answer"]');
                    if (firstRadio) firstRadio.checked = true;
                }
                updateCorrectHighlight();
            } else {
                alert('{{ __('messages.minimum_two_options') ?? 'At least 2 options are required.' }}');
            }
        }

        // Listen for radio selection changes to update row green highlight
        document.getElementById('optionsBody').addEventListener('change', function(e) {
            if (e.target && e.target.name === 'correct_answer') {
                updateCorrectHighlight();
            }
        });

        // Validate that single choice has a correct option selected before submit
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            const type = document.getElementById('question_type').value;
            if (type === 'radio') {
                const checkedRadio = document.querySelector('input[name="correct_answer"]:checked');
                if (!checkedRadio) {
                    e.preventDefault();
                    alert('{{ __('messages.please_select_correct_option') ?? 'Please select the correct option for single choice questions.' }}');
                    return false;
                }
            } else {
                // Ensure correct_answer is disabled for non-radio questions
                document.querySelectorAll('input[name="correct_answer"]').forEach(r => {
                    r.disabled = true;
                });
            }
        });

        // Initialize
        document.getElementById('question_type').addEventListener('change', toggleOptionsSection);
        toggleOptionsSection();
    </script>
@endsection
