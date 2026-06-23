@extends('layouts.business')

@section('title', __('messages.profile'))

@section('pagewisestyle')
    <link rel="stylesheet" href="{{ url('assets/vendor/dropify/dropify.min.css') }}">
@endsection

@section('pagewisescript')
    <script src="{{ url('assets/vendor/dropify/dropify.min.js') }}"></script>
@endsection

@section('customjs')
    <script type="text/javascript">
        $('.dropify').dropify();

        function copyToClipboard() {
            var copyText = document.getElementById("business_code");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(() => {
                alert("{{ __('messages.business_code') }} copied: " + copyText.value);
            });
        }
    </script>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.business_profile') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('messages.profile') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-9">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.edit_profile') }}</h5>
                        <form class="row g-3 needs-validation" action="{{ route('business.profile.update') }}"
                            method="POST" enctype="multipart/form-data" novalidate>
                            @csrf

                            <div class="col-12 position-relative">
                                <label for="name" class="form-label">{{ __('messages.business_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                    value="{{ $business->name }}">
                                @if ($errors->has('name'))
                                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                @endif
                            </div>

                            <div class="col-12 position-relative">
                                <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                <input type="email" class="form-control" id="email" value="{{ $business->email }}"
                                    disabled>
                                <small class="text-muted">{{ __('messages.email_cannot_change') }}</small>
                            </div>

                            <div class="col-12 position-relative">
                                <label for="business_code"
                                    class="form-label">{{ __('messages.your_business_code') }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="business_code"
                                        value="{{ $business->business_code }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                                        <i class="bi bi-clipboard"></i> {{ __('messages.copy') }}
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('messages.share_code_hint') }}</small>
                            </div>

                            <div class="col-12 position-relative">
                                <label for="logo" class="form-label">{{ __('messages.logo') }}</label>
                                <div class="{{ $errors->has('logo') ? 'is-invalid' : '' }}">
                                    <input type="file" name="logo"
                                        class="dropify {{ $errors->has('logo') ? 'is-invalid' : '' }}" id="logo"
                                        data-default-file="{{ $business->logo && Storage::exists('public/business_logos/' . $business->logo) ? asset('storage/app/public/business_logos/' . $business->logo) : '' }}">
                                </div>
                                <label class="pl-1 mt-1 col-md-12 col-lg-12">{{ __('messages.recommended_size') }}</label>
                                @if ($errors->has('logo'))
                                    <div class="invalid-feedback">{{ $errors->first('logo') }}</div>
                                @endif
                            </div>

                            <div class="col-12 position-relative">
                                <label for="address" class="form-label">{{ __('messages.address') }}</label>
                                <textarea name="address" id="address" rows="3"
                                    class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}">{{ $business->address }}</textarea>
                                @if ($errors->has('address'))
                                    <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                                @endif
                            </div>

                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
                                <a href="{{ route('business.dashboard') }}"
                                    class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
