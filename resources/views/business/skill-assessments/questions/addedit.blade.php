@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_question') : __('messages.add_question'))

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
            <div class="col-lg-10">
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

                            <div class="mb-3">
                                <label for="question_text"
                                    class="form-label">{{ __('messages.question_text') ?? 'Question Text' }} <span
                                        class="text-danger">*</span></label>
                                <textarea name="question_text" id="question_text" class="form-control @error('question_text') is-invalid @enderror"
                                    rows="3" required>{{ old('question_text', $data->question_text ?? '') }}</textarea>
                                @error('question_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="helper_text"
                                    class="form-label">{{ __('messages.helper_text') ?? 'Helper Text' }}</label>
                                <input type="text" name="helper_text" id="helper_text" class="form-control"
                                    value="{{ old('helper_text', $data->helper_text ?? '') }}">
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

                                <table class="table table-bordered" id="optionsTable">
                                    <thead>
                                        <tr>
                                            <th width="40">{{ __('messages.correct') ?? 'Correct' }}</th>
                                            <th>{{ __('messages.option_text') ?? 'Option Text' }}</th>
                                            <th width="120">{{ __('messages.weightage') ?? 'Weightage' }}</th>
                                            <th width="60">{{ __('messages.action') ?? 'Action' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="optionsBody">
                                        @if (isset($data) && $data->options->count() > 0)
                                            @foreach ($data->options as $index => $option)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="radio" name="correct_answer"
                                                            value="{{ $index }}"
                                                            class="form-check-input correct-radio"
                                                            {{ $option->is_correct ? 'checked' : '' }}>
                                                        <input type="checkbox"
                                                            name="options[{{ $index }}][is_correct]"
                                                            class="form-check-input correct-checkbox"
                                                            style="display: none;"
                                                            {{ $option->is_correct ? 'checked' : '' }}>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="options[{{ $index }}][option_text]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $option->option_text }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="options[{{ $index }}][weightage]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $option->weightage }}" min="0"
                                                            step="0.01">
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
                                            <tr>
                                                <td class="text-center">
                                                    <input type="radio" name="correct_answer" value="0"
                                                        class="form-check-input correct-radio">
                                                    <input type="checkbox" name="options[0][is_correct]"
                                                        class="form-check-input correct-checkbox" style="display: none;">
                                                </td>
                                                <td>
                                                    <input type="text" name="options[0][option_text]"
                                                        class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="options[0][weightage]"
                                                        class="form-control form-control-sm" value="0"
                                                        min="0" step="0.01">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="removeOption(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="radio" name="correct_answer" value="1"
                                                        class="form-check-input correct-radio">
                                                    <input type="checkbox" name="options[1][is_correct]"
                                                        class="form-check-input correct-checkbox" style="display: none;">
                                                </td>
                                                <td>
                                                    <input type="text" name="options[1][option_text]"
                                                        class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="options[1][weightage]"
                                                        class="form-control form-control-sm" value="0"
                                                        min="0" step="0.01">
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

        function toggleOptionsSection() {
            const type = document.getElementById('question_type').value;
            const optionsSection = document.getElementById('optionsSection');
            const radioInputs = document.querySelectorAll('.correct-radio');
            const checkboxInputs = document.querySelectorAll('.correct-checkbox');

            if (type === 'radio' || type === 'multi_select') {
                optionsSection.style.display = 'block';

                if (type === 'radio') {
                    radioInputs.forEach(input => input.style.display = '');
                    checkboxInputs.forEach(input => input.style.display = 'none');
                } else {
                    radioInputs.forEach(input => input.style.display = 'none');
                    checkboxInputs.forEach(input => input.style.display = '');
                }
            } else {
                optionsSection.style.display = 'none';
            }
        }

        function addOption() {
            const type = document.getElementById('question_type').value;
            const isRadio = type === 'radio';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center">
                    <input type="radio" name="correct_answer" value="${optionIndex}" class="form-check-input correct-radio" ${isRadio ? '' : 'style="display: none;"'}>
                    <input type="checkbox" name="options[${optionIndex}][is_correct]" class="form-check-input correct-checkbox" ${isRadio ? 'style="display: none;"' : ''}>
                </td>
                <td>
                    <input type="text" name="options[${optionIndex}][option_text]" class="form-control form-control-sm" required>
                </td>
                <td>
                    <input type="number" name="options[${optionIndex}][weightage]" class="form-control form-control-sm" value="0" min="0" step="0.01">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            document.getElementById('optionsBody').appendChild(row);
            optionIndex++;
        }

        function removeOption(btn) {
            const tbody = document.getElementById('optionsBody');
            if (tbody.rows.length > 2) {
                btn.closest('tr').remove();
            } else {
                alert('{{ __('messages.minimum_two_options') ?? 'At least 2 options are required.' }}');
            }
        }

        // Initialize
        document.getElementById('question_type').addEventListener('change', toggleOptionsSection);
        toggleOptionsSection();
    </script>
@endsection
