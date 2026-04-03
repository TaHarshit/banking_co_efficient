@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_section') : __('messages.add_section'))

@section('content')
    <div class="pagetitle">
        <h1>{{ isset($data) ? __('messages.edit_section') : __('messages.add_section') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('business.sections') }}">{{ __('messages.sections') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ isset($data) ? __('messages.edit') : __('messages.add') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            {{ isset($data) ? __('messages.edit_section') : __('messages.add_section') }}</h5>

                        <form action="{{ route('business.sections.store') }}" method="POST" class="row g-3">
                            @csrf
                            <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                            <div class="col-md-6">
                                <label for="subtitle_en" class="form-label">{{ __('messages.subtitle') }} (English)</label>
                                <input type="text" name="subtitle_en"
                                    class="form-control {{ $errors->has('subtitle_en') ? 'is-invalid' : '' }}"
                                    id="subtitle_en" value="{{ isset($data) ? $data->subtitle_en : old('subtitle_en') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="subtitle_fr" class="form-label">{{ __('messages.subtitle') }} (French)</label>
                                <input type="text" name="subtitle_fr"
                                    class="form-control {{ $errors->has('subtitle_fr') ? 'is-invalid' : '' }}"
                                    id="subtitle_fr" value="{{ isset($data) ? $data->subtitle_fr : old('subtitle_fr') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="header_en" class="form-label">{{ __('messages.header') }} (English)</label>
                                <input type="text" name="header_en"
                                    class="form-control {{ $errors->has('header_en') ? 'is-invalid' : '' }}" id="header_en"
                                    value="{{ isset($data) ? $data->header_en : old('header_en') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="header_fr" class="form-label">{{ __('messages.header') }} (French)</label>
                                <input type="text" name="header_fr"
                                    class="form-control {{ $errors->has('header_fr') ? 'is-invalid' : '' }}" id="header_fr"
                                    value="{{ isset($data) ? $data->header_fr : old('header_fr') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="order" class="form-label">{{ __('messages.order') }}</label>
                                <input type="number" name="order"
                                    class="form-control {{ $errors->has('order') ? 'is-invalid' : '' }}" id="order"
                                    value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                    min="1">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                        {{ isset($data) ? ($data->is_active ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('messages.active') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($data) ? __('messages.update_section') : __('messages.add_section') }}
                                </button>
                                <a href="{{ route('business.sections') }}"
                                    class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
