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
            <h1>{{ isset($data) ? __('messages.edit_section') : __('messages.add_section') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('managesections') }}">{{ __('messages.sections') }}</a></li>
                    <li class="breadcrumb-item active">
                        {{ isset($data) ? __('messages.edit_section') : __('messages.add_section') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ isset($data) ? __('messages.edit_section') : __('messages.add_section') }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('storesection') }}" method="POST"
                                novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                                {{-- Section Title - Bilingual --}}
                                <div class="col-12">
                                    <label class="form-label fw-bold">{{ __('messages.section_title') }} <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="title_en" class="form-label">🇬🇧 {{ __('messages.english') }}</label>
                                    <input type="text" name="title_en"
                                        class="form-control {{ $errors->has('title_en') ? 'is-invalid' : '' }}"
                                        id="title_en" value="{{ isset($data) ? $data->title_en : old('title_en') }}"
                                        placeholder="e.g., Professional Profile">
                                    @if ($errors->has('title_en'))
                                        <div class="invalid-feedback">{{ $errors->first('title_en') }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="title_fr" class="form-label">🇫🇷 {{ __('messages.french') }}</label>
                                    <input type="text" name="title_fr"
                                        class="form-control {{ $errors->has('title_fr') ? 'is-invalid' : '' }}"
                                        id="title_fr" value="{{ isset($data) ? $data->title_fr : old('title_fr') }}"
                                        placeholder="ex: Profil Professionnel">
                                    @if ($errors->has('title_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('title_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Section Header - Bilingual --}}
                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">{{ __('messages.section_header') }}</label>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="header_en" class="form-label">🇬🇧 {{ __('messages.english') }}</label>
                                    <input type="text" name="header_en" class="form-control" id="header_en"
                                        value="{{ isset($data) ? $data->header_en : old('header_en') }}"
                                        placeholder="e.g., Professional Context">
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="header_fr" class="form-label">🇫🇷 {{ __('messages.french') }}</label>
                                    <input type="text" name="header_fr" class="form-control" id="header_fr"
                                        value="{{ isset($data) ? $data->header_fr : old('header_fr') }}"
                                        placeholder="ex: Contexte Professionnel">
                                </div>

                                {{-- Section Subtitle - Bilingual --}}
                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">{{ __('messages.section_subtitle') }}</label>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="subtitle_en" class="form-label">🇬🇧 {{ __('messages.english') }}</label>
                                    <input type="text" name="subtitle_en" class="form-control" id="subtitle_en"
                                        value="{{ isset($data) ? $data->subtitle_en : old('subtitle_en') }}"
                                        placeholder="e.g., Your banking environment">
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="subtitle_fr" class="form-label">🇫🇷 {{ __('messages.french') }}</label>
                                    <input type="text" name="subtitle_fr" class="form-control" id="subtitle_fr"
                                        value="{{ isset($data) ? $data->subtitle_fr : old('subtitle_fr') }}"
                                        placeholder="ex: Votre environnement bancaire">
                                </div>

                                {{-- Order --}}
                                <div class="col-md-4 position-relative mt-4">
                                    <label for="order" class="form-label">{{ __('messages.section_order') }}</label>
                                    <input type="number" name="order" class="form-control" id="order"
                                        value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                        min="1">
                                </div>

                                {{-- Status --}}
                                <div class="col-md-4 position-relative mt-4">
                                    <label class="form-label">{{ __('messages.status') }}</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                            {{ (isset($data) && $data->is_active) || !isset($data) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="is_active">{{ __('messages.active') }}</label>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ isset($data) ? __('messages.save') : __('messages.add') }}
                                    </button>
                                    <a href="{{ route('managesections') }}" class="btn btn-secondary">
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
