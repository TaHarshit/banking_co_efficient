@extends('layouts.business')

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
        </h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.skill-assessment.exams') }}">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</a>
                </li>
                <li class="breadcrumb-item active">
                    {{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
                </li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            {{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
                        </h5>
                        <form class="row g-3 needs-validation" action="{{ route('business.skill-assessment.exams.store') }}"
                            method="POST" novalidate>
                            @csrf
                            <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                            {{-- Exam Title EN --}}
                            <div class="col-md-6 mt-3">
                                <label for="title"
                                    class="form-label fw-bold">{{ __('messages.title_en') ?? 'Title (EN)' }}
                                    <span class="text-danger">*</span></label>
                                <input type="text" name="title"
                                    class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title"
                                    value="{{ isset($data) ? $data->title : old('title') }}"
                                    placeholder="e.g., Banking Knowledge Assessment" required>
                                @if ($errors->has('title'))
                                    <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                                @endif
                            </div>

                            {{-- Exam Title FR --}}
                            <div class="col-md-6 mt-3">
                                <label for="title_fr"
                                    class="form-label fw-bold">{{ __('messages.title_fr') ?? 'Title (FR)' }}</label>
                                <input type="text" name="title_fr"
                                    class="form-control {{ $errors->has('title_fr') ? 'is-invalid' : '' }}" id="title_fr"
                                    value="{{ isset($data) ? $data->title_fr : old('title_fr') }}"
                                    placeholder="e.g., Évaluation des connaissances bancaires">
                                @if ($errors->has('title_fr'))
                                    <div class="invalid-feedback">{{ $errors->first('title_fr') }}</div>
                                @endif
                            </div>

                            {{-- Tag EN --}}
                            <div class="col-md-6 mt-3">
                                <label for="tag" class="form-label">{{ __('messages.tag_en') ?? 'Tag (EN)' }}</label>
                                <input type="text" name="tag"
                                    class="form-control {{ $errors->has('tag') ? 'is-invalid' : '' }}" id="tag"
                                    value="{{ isset($data) ? $data->tag : old('tag') }}"
                                    placeholder="e.g., Beginner, Intermediate">
                                @if ($errors->has('tag'))
                                    <div class="invalid-feedback">{{ $errors->first('tag') }}</div>
                                @endif
                            </div>

                            {{-- Tag FR --}}
                            <div class="col-md-6 mt-3">
                                <label for="tag_fr" class="form-label">{{ __('messages.tag_fr') ?? 'Tag (FR)' }}</label>
                                <input type="text" name="tag_fr"
                                    class="form-control {{ $errors->has('tag_fr') ? 'is-invalid' : '' }}" id="tag_fr"
                                    value="{{ isset($data) ? $data->tag_fr : old('tag_fr') }}"
                                    placeholder="e.g., Débutant, Intermédiaire">
                                @if ($errors->has('tag_fr'))
                                    <div class="invalid-feedback">{{ $errors->first('tag_fr') }}</div>
                                @endif
                            </div>

                            {{-- Exam Description EN --}}
                            <div class="col-md-6 mt-3">
                                <label for="description"
                                    class="form-label">{{ __('messages.description_en') ?? 'Description (EN)' }}</label>
                                <textarea name="description" class="form-control" id="description" rows="3"
                                    placeholder="Optional description of this exam">{{ isset($data) ? $data->description : old('description') }}</textarea>
                            </div>

                            {{-- Exam Description FR --}}
                            <div class="col-md-6 mt-3">
                                <label for="description_fr"
                                    class="form-label">{{ __('messages.description_fr') ?? 'Description (FR)' }}</label>
                                <textarea name="description_fr" class="form-control" id="description_fr" rows="3"
                                    placeholder="Description facultative">{{ isset($data) ? $data->description_fr : old('description_fr') }}</textarea>
                            </div>

                            {{-- Duration --}}
                            <div class="col-md-4 position-relative mt-3">
                                <label for="duration_minutes"
                                    class="form-label">{{ __('messages.duration_minutes') ?? 'Duration (minutes)' }}</label>
                                <input type="number" name="duration_minutes"
                                    class="form-control {{ $errors->has('duration_minutes') ? 'is-invalid' : '' }}"
                                    id="duration_minutes"
                                    value="{{ isset($data) ? $data->duration_minutes : old('duration_minutes') }}"
                                    min="1" placeholder="Optional">
                                @if ($errors->has('duration_minutes'))
                                    <div class="invalid-feedback">{{ $errors->first('duration_minutes') }}</div>
                                @endif
                            </div>

                            {{-- Passing Percentage --}}
                            <div class="col-md-4 position-relative mt-3">
                                <label for="passing_percentage"
                                    class="form-label">{{ __('messages.passing_percentage') ?? 'Passing Percentage' }}</label>
                                <input type="number" name="passing_percentage"
                                    class="form-control {{ $errors->has('passing_percentage') ? 'is-invalid' : '' }}"
                                    id="passing_percentage"
                                    value="{{ isset($data) ? $data->passing_percentage : old('passing_percentage') }}"
                                    min="0" max="100" step="0.01" placeholder="Optional">
                                @if ($errors->has('passing_percentage'))
                                    <div class="invalid-feedback">{{ $errors->first('passing_percentage') }}</div>
                                @endif
                            </div>

                            {{-- Order --}}
                            <div class="col-md-4 position-relative mt-3">
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

                            {{-- Status --}}
                            <div class="col-md-4 position-relative mt-3">
                                <label class="form-label">{{ __('messages.status') ?? 'Status' }}</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                        {{ (isset($data) && $data->is_active) || !isset($data) ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                        for="is_active">{{ __('messages.active') ?? 'Active' }}</label>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($data) ? __('messages.save') ?? 'Save' : __('messages.add') ?? 'Add' }}
                                </button>
                                <a href="{{ route('business.skill-assessment.exams') }}" class="btn btn-secondary">
                                    {{ __('messages.cancel') ?? 'Cancel' }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
