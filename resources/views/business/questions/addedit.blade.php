@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_question') : __('messages.add_question'))

@section('content')
    <div class="pagetitle">
        <h1>{{ isset($data) ? __('messages.edit_question') : __('messages.add_question') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('business.sections') }}">{{ __('messages.sections') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.questions', $section->id) }}">{{ __('messages.questions') }}</a>
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
                            {{ isset($data) ? __('messages.edit_question') : __('messages.add_question') }}</h5>

                        <form action="{{ route('business.questions.store', $section->id) }}" method="POST" class="row g-3">
                            @csrf
                            <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                            <div class="col-md-6">
                                <label for="question_type" class="form-label">{{ __('messages.question_type') }} <span
                                        class="text-danger">*</span></label>
                                <select name="question_type" id="question_type"
                                    class="form-select {{ $errors->has('question_type') ? 'is-invalid' : '' }}" required>
                                    <option value="">{{ __('messages.select_type') }}</option>
                                    @foreach ($typeOptions as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ isset($data) && $data->question_type == $value ? 'selected' : (old('question_type') == $value ? 'selected' : '') }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('question_type'))
                                    <div class="invalid-feedback">{{ $errors->first('question_type') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="order" class="form-label">{{ __('messages.order') }}</label>
                                <input type="number" name="order" class="form-control" id="order"
                                    value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                    min="1">
                            </div>

                            <div class="col-md-6">
                                <label for="question_text_en" class="form-label">{{ __('messages.question_text') }}
                                    (English) <span class="text-danger">*</span></label>
                                <textarea name="question_text_en" id="question_text_en" rows="3"
                                    class="form-control {{ $errors->has('question_text_en') ? 'is-invalid' : '' }}" required>{{ isset($data) ? $data->question_text_en : old('question_text_en') }}</textarea>
                                @if ($errors->has('question_text_en'))
                                    <div class="invalid-feedback">{{ $errors->first('question_text_en') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="question_text_fr" class="form-label">{{ __('messages.question_text') }}
                                    (French) <span class="text-danger">*</span></label>
                                <textarea name="question_text_fr" id="question_text_fr" rows="3"
                                    class="form-control {{ $errors->has('question_text_fr') ? 'is-invalid' : '' }}" required>{{ isset($data) ? $data->question_text_fr : old('question_text_fr') }}</textarea>
                                @if ($errors->has('question_text_fr'))
                                    <div class="invalid-feedback">{{ $errors->first('question_text_fr') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="helper_text_en" class="form-label">{{ __('messages.helper_text') }}
                                    (English)</label>
                                <input type="text" name="helper_text_en" class="form-control" id="helper_text_en"
                                    value="{{ isset($data) ? $data->helper_text_en : old('helper_text_en') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="helper_text_fr" class="form-label">{{ __('messages.helper_text') }}
                                    (French)</label>
                                <input type="text" name="helper_text_fr" class="form-control" id="helper_text_fr"
                                    value="{{ isset($data) ? $data->helper_text_fr : old('helper_text_fr') }}">
                            </div>

                            <!-- Rating Scale Settings -->
                            <div class="col-12" id="rating_settings"
                                style="{{ isset($data) && $data->question_type == 'rating_scale' ? '' : 'display:none' }}">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>{{ __('messages.rating_settings') }}</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="min_value"
                                                    class="form-label">{{ __('messages.min_value') }}</label>
                                                <input type="number" name="min_value" class="form-control" id="min_value"
                                                    value="{{ isset($data) && isset($data->settings['min_value']) ? $data->settings['min_value'] : 1 }}"
                                                    min="1" max="10">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="max_value"
                                                    class="form-label">{{ __('messages.max_value') }}</label>
                                                <input type="number" name="max_value" class="form-control" id="max_value"
                                                    value="{{ isset($data) && isset($data->settings['max_value']) ? $data->settings['max_value'] : 5 }}"
                                                    min="1" max="10">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Options for Select Type Questions -->
                            <div class="col-12" id="options_container"
                                style="{{ isset($data) && in_array($data->question_type, ['single_select', 'multi_select']) ? '' : 'display:none' }}">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">{{ __('messages.options') }}</h6>
                                            <button type="button" class="btn btn-sm btn-success" onclick="addOption()">
                                                <i class="bi bi-plus"></i> {{ __('messages.add_option') }}
                                            </button>
                                        </div>
                                        <div id="options_list">
                                            @if (isset($data) && $data->options->count() > 0)
                                                @foreach ($data->options as $index => $option)
                                                    <div class="option-row row mb-2">
                                                        <div class="col-md-5">
                                                            <input type="text" name="option_text_en[]"
                                                                class="form-control" placeholder="Option (English)"
                                                                value="{{ $option->option_text_en }}">
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" name="option_text_fr[]"
                                                                class="form-control" placeholder="Option (French)"
                                                                value="{{ $option->option_text_fr }}">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="removeOption(this)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="option-row row mb-2">
                                                    <div class="col-md-5">
                                                        <input type="text" name="option_text_en[]"
                                                            class="form-control" placeholder="Option (English)">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" name="option_text_fr[]"
                                                            class="form-control" placeholder="Option (French)">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="removeOption(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_required" id="is_required"
                                        {{ isset($data) ? ($data->is_required ? 'checked' : '') : '' }}>
                                    <label class="form-check-label" for="is_required">
                                        {{ __('messages.required') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                        {{ isset($data) ? ($data->is_active ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('messages.active') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($data) ? __('messages.update_question') : __('messages.add_question') }}
                                </button>
                                <a href="{{ route('business.questions', $section->id) }}"
                                    class="btn btn-secondary">{{ __('messages.cancel') }}</a>
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
        // Toggle sections based on question type
        document.getElementById('question_type').addEventListener('change', function() {
            var type = this.value;
            document.getElementById('rating_settings').style.display = (type === 'rating_scale') ? '' : 'none';
            document.getElementById('options_container').style.display = (type === 'single_select' || type ===
                'multi_select') ? '' : 'none';
        });

        // Add new option row
        function addOption() {
            var html = `
                <div class="option-row row mb-2">
                    <div class="col-md-5">
                        <input type="text" name="option_text_en[]" class="form-control" placeholder="Option (English)">
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="option_text_fr[]" class="form-control" placeholder="Option (French)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('options_list').insertAdjacentHTML('beforeend', html);
        }

        // Remove option row
        function removeOption(btn) {
            var rows = document.querySelectorAll('.option-row');
            if (rows.length > 1) {
                btn.closest('.option-row').remove();
            }
        }
    </script>
@endsection
