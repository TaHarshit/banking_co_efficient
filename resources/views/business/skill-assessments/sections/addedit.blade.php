@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_section') : __('messages.add_section'))

@section('content')
    <div class="pagetitle">
        <h1>{{ isset($data) ? __('messages.edit_section') ?? 'Edit Section' : __('messages.add_section') ?? 'Add Section' }}
        </h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                @if (isset($examTemplate))
                    <li class="breadcrumb-item"><a
                            href="{{ route('business.skill-assessment.exams') }}">{{ __('messages.skill_assessment_exams') ?? 'Skill Assessment Exams' }}</a>
                    </li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('business.skill-assessment.sections', ['exam_template_id' => $examTemplate->id]) }}">{{ $examTemplate->title }}
                            - {{ __('messages.sections') }}</a></li>
                @else
                    <li class="breadcrumb-item"><a
                            href="{{ route('business.skill-assessment.sections') }}">{{ __('messages.skill_assessment_sections') ?? 'Skill Assessment Sections' }}</a>
                    </li>
                @endif
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
                            {{ isset($data) ? __('messages.edit_section') ?? 'Edit Section' : __('messages.add_section') ?? 'Add Section' }}
                        </h5>

                        <form action="{{ route('business.skill-assessment.sections.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $data->id ?? 0 }}">
                            @if (isset($examTemplateId))
                                <input type="hidden" name="skill_assessment_exam_template_id"
                                    value="{{ $examTemplateId }}">
                            @endif

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">{{ __('messages.title_en') ?? 'Title (EN)' }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title"
                                        class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title', isset($data) ? ($data->getRawOriginal('title') ?? $data->title) : '') }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="title_fr" class="form-label">{{ __('messages.title_fr') ?? 'Title (FR)' }}</label>
                                    <input type="text" name="title_fr" id="title_fr"
                                        class="form-control @error('title_fr') is-invalid @enderror"
                                        value="{{ old('title_fr', $data->title_fr ?? '') }}"
                                        placeholder="{{ __('messages.title_fr_placeholder') ?? 'Titre de la section en français' }}">
                                    @error('title_fr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="description" class="form-label">{{ __('messages.description_en') ?? 'Description (EN)' }}</label>
                                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', isset($data) ? ($data->getRawOriginal('description') ?? $data->description) : '') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="description_fr" class="form-label">{{ __('messages.description_fr') ?? 'Description (FR)' }}</label>
                                    <textarea name="description_fr" id="description_fr" class="form-control" rows="4"
                                        placeholder="{{ __('messages.description_fr_placeholder') ?? 'Description facultative en français' }}">{{ old('description_fr', $data->description_fr ?? '') }}</textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="order" class="form-label">{{ __('messages.order') }}</label>
                                        <input type="number" name="order" id="order" class="form-control"
                                            value="{{ old('order', $data->order ?? ($nextOrder ?? 1)) }}" min="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 pt-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                                {{ old('is_active', $data->is_active ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="is_active">{{ __('messages.active') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i>
                                    {{ isset($data) ? __('messages.update') : __('messages.save') }}
                                </button>
                                <a href="{{ route('business.skill-assessment.sections') }}" class="btn btn-secondary">
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
