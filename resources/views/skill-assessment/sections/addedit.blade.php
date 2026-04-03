@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ isset($data) ? __('messages.edit_section') ?? 'Edit Section' : __('messages.add_section') ?? 'Add Section' }}
            </h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</a>
                    </li>
                    @if (isset($examTemplate) && $examTemplate)
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentsections') }}?exam_template_id={{ $examTemplateId }}">{{ $examTemplate->title }}</a>
                        </li>
                    @else
                        <li class="breadcrumb-item"><a
                                href="{{ route('manageskillassessmentsections') }}">{{ __('messages.sections') ?? 'Sections' }}</a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active">
                        {{ isset($data) ? __('messages.edit_section') ?? 'Edit Section' : __('messages.add_section') ?? 'Add Section' }}
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
                                {{ isset($data) ? __('messages.edit_section') ?? 'Edit Section' : __('messages.add_section') ?? 'Add Section' }}
                            </h5>
                            <form class="row g-3 needs-validation" action="{{ route('storeskillassessmentsection') }}"
                                method="POST" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">
                                <input type="hidden" name="skill_assessment_exam_template_id"
                                    value="{{ $examTemplateId ?? (isset($data) ? $data->skill_assessment_exam_template_id : '') }}">

                                {{-- Section Title EN --}}
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

                                {{-- Section Title FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="title_fr"
                                        class="form-label fw-bold">{{ __('messages.title_fr') ?? 'Title (FR)' }}</label>
                                    <input type="text" name="title_fr"
                                        class="form-control {{ $errors->has('title_fr') ? 'is-invalid' : '' }}"
                                        id="title_fr" value="{{ isset($data) ? $data->title_fr : old('title_fr') }}"
                                        placeholder="e.g., Évaluation des connaissances bancaires">
                                    @if ($errors->has('title_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('title_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Section Description EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="description"
                                        class="form-label">{{ __('messages.description_en') ?? 'Description (EN)' }}</label>
                                    <textarea name="description" class="form-control" id="description" rows="3"
                                        placeholder="Optional description of this assessment section">{{ isset($data) ? $data->description : old('description') }}</textarea>
                                </div>

                                {{-- Section Description FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="description_fr"
                                        class="form-label">{{ __('messages.description_fr') ?? 'Description (FR)' }}</label>
                                    <textarea name="description_fr" class="form-control" id="description_fr" rows="3"
                                        placeholder="Description facultative">{{ isset($data) ? $data->description_fr : old('description_fr') }}</textarea>
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
                                    @if (isset($examTemplateId) && $examTemplateId)
                                        <a href="{{ route('manageskillassessmentsections') }}?exam_template_id={{ $examTemplateId }}"
                                            class="btn btn-secondary">
                                            {{ __('messages.cancel') ?? 'Cancel' }}
                                        </a>
                                    @else
                                        <a href="{{ route('manageskillassessmentsections') }}" class="btn btn-secondary">
                                            {{ __('messages.cancel') ?? 'Cancel' }}
                                        </a>
                                    @endif
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
