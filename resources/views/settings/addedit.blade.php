@extends('layouts.app')
@section('pagewisestyle')
    <link rel="stylesheet" href="{{ url('assets/vendor/dropify/dropify.min.css') }}">
@endsection
@section('pagewisescript')
    <script src="{{ url('assets/vendor/dropify/dropify.min.js') }}"></script>
@endsection
@section('customjs')
    <script type="text/javascript">
        $('.dropify').dropify();
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.settings') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.settings') }}</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-9">
                    <!-- @include('partials.messages') -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.settings') }}</h5>
                            <form class="row g-3 needs-validation"
                                action="{{ route('storesettings', ['id' => isset($data) ? $data->id : '0']) }}"
                                method="POST"enctype="multipart/form-data" novalidate>
                                @csrf
                                <div class="col-12 position-relative">
                                    <label for="feedback_form_link"
                                        class="form-label">{{ __('messages.feedback_form_link') }}
                                    </label>
                                    <input type="text" name="feedback_form_link"
                                        class="form-control {{ $errors->has('feedback_form_link') ? 'is-invalid' : '' }}"
                                        id="feedback_form_link"
                                        value="{{ isset($data) ? $data->feedback_form_link : old('feedback_form_link') }}">
                                    @if ($errors->has('feedback_form_link'))
                                        <div class="invalid-feedback">{{ $errors->first('feedback_form_link') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="feedback_form_link_fr"
                                        class="form-label">{{ __('messages.feedback_form_link_fr') }}
                                    </label>
                                    <input type="text" name="feedback_form_link_fr"
                                        class="form-control {{ $errors->has('feedback_form_link_fr') ? 'is-invalid' : '' }}"
                                        id="feedback_form_link_fr"
                                        value="{{ isset($data) ? $data->feedback_form_link_fr : old('feedback_form_link_fr') }}">
                                    @if ($errors->has('feedback_form_link_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('feedback_form_link_fr') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="user_android_version"
                                        class="form-label">{{ __('messages.user_android_version') }}</label>
                                    <input type="text" name="user_android_version"
                                        class="form-control {{ $errors->has('user_android_version') ? 'is-invalid' : '' }}"
                                        id="user_android_version"
                                        value="{{ isset($data) ? $data->user_android_version : old('user_android_version') }}">
                                    @if ($errors->has('user_android_version'))
                                        <div class="invalid-feedback">{{ $errors->first('user_android_version') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="user_ios_version" class="form-label">{{ __('messages.user_ios_version') }}
                                    </label>
                                    <input type="text" name="user_ios_version"
                                        class="form-control {{ $errors->has('user_ios_version') ? 'is-invalid' : '' }}"
                                        id="user_ios_version"
                                        value="{{ isset($data) ? $data->user_ios_version : old('user_ios_version') }}">
                                    @if ($errors->has('user_ios_version'))
                                        <div class="invalid-feedback">{{ $errors->first('user_ios_version') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="android_build_number"
                                        class="form-label">{{ __('messages.android_build_number') }}</label>
                                    <input type="text" name="android_build_number"
                                        class="form-control {{ $errors->has('android_build_number') ? 'is-invalid' : '' }}"
                                        id="android_build_number"
                                        value="{{ isset($data) ? $data->android_build_number : old('android_build_number') }}">
                                    @if ($errors->has('android_build_number'))
                                        <div class="invalid-feedback">{{ $errors->first('android_build_number') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="ios_build_number" class="form-label">{{ __('messages.ios_build_number') }}
                                    </label>
                                    <input type="text" name="ios_build_number"
                                        class="form-control {{ $errors->has('ios_build_number') ? 'is-invalid' : '' }}"
                                        id="ios_build_number"
                                        value="{{ isset($data) ? $data->ios_build_number : old('ios_build_number') }}">
                                    @if ($errors->has('ios_build_number'))
                                        <div class="invalid-feedback">{{ $errors->first('ios_build_number') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="privacy_policy"
                                        class="form-label">{{ __('messages.privacy_policy') }}</label>
                                    <textarea name="privacy_policy" id="privacy_policy" rows="5" class="tinymce-editor">{!! $data->privacy_policy !!}</textarea>
                                    @if ($errors->has('privacy_policy'))
                                        <div class="invalid-feedback">{{ $errors->first('privacy_policy') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="privacy_policy_fr"
                                        class="form-label">{{ __('messages.privacy_policy_fr') }}</label>
                                    <textarea name="privacy_policy_fr" id="privacy_policy_fr" rows="5" class="tinymce-editor">{!! $data->privacy_policy_fr !!}</textarea>
                                    @if ($errors->has('privacy_policy_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('privacy_policy_fr') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="terms_and_conditions"
                                        class="form-label">{{ __('messages.terms_and_conditions') }}</label>
                                    <textarea name="terms_and_conditions" id="terms_and_conditions" rows="5" class="tinymce-editor">{!! $data->terms_and_conditions !!}</textarea>
                                    @if ($errors->has('terms_and_conditions'))
                                        <div class="invalid-feedback">{{ $errors->first('terms_and_conditions') }}</div>
                                    @endif
                                </div>
                                
                                <div class="col-12 position-relative">
                                    <label for="terms_and_conditions_fr"
                                        class="form-label">{{ __('messages.terms_and_conditions_fr') }}</label>
                                    <textarea name="terms_and_conditions_fr" id="terms_and_conditions_fr" rows="5" class="tinymce-editor">{!! $data->terms_and_conditions_fr !!}</textarea>
                                    @if ($errors->has('terms_and_conditions_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('terms_and_conditions_fr') }}</div>
                                    @endif
                                </div>

                                <div class="col-sm-10">
                                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
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
