@extends('layouts.app')
@section('pagewisestyle')
    <style>
        ol li {
            color: rgb(106 177 58);
            font-weight: 700;
        }

        .qr-title {
            color: rgb(228 94 13) !important;
        }

        /* Language Flag Buttons */
        .lang-flag-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 6px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .lang-flag-btn:hover {
            border-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
        }

        .lang-flag-btn.active {
            border-color: #0d6efd;
            background: #f0f7ff;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
        }

        .lang-flag-btn img {
            width: 28px;
            height: 18px;
            display: block;
        }
    </style>
@endsection
@section('customjs')
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="d-flex justify-content-center py-4">
                                <a href="index.html" class="logo d-flex align-items-center w-auto">
                                    <img src="{{ url('assets/img/logo.png') }}" alt="">
                                    {{-- <span class="d-none d-lg-block">{{env('APP_NAME')}}</span> --}}
                                </a>
                            </div><!-- End Logo -->
                            <div class="card mb-3" style="width:100%;">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">{{ __('messages.login_title') }}</h5>
                                        <p class="text-center small">{{ __('messages.login_subtitle') }}</p>
                                    </div>
                                    @include('partials.messages')
                                    <form class="row g-3 needs-validation" action="{{ route('signin') }}" method="POST"
                                        novalidate>
                                        @csrf
                                        <div class="col-12 position-relative">
                                            <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                            <input type="text" name="email"
                                                class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                                id="email">
                                            @if ($errors->has('email'))
                                                <div class="invalid-tooltip">{{ $errors->first('email') }}</div>
                                            @endif
                                        </div>
                                        <div class="col-12 position-relative">
                                            <label for="password" class="form-label">{{ __('messages.password') }}</label>
                                            <input type="password" name="password"
                                                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                                id="password">
                                            @if ($errors->has('password'))
                                                <div class="invalid-tooltip">{{ $errors->first('password') }}</div>
                                            @endif
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember_me"
                                                    value="true" id="remember_me">
                                                <label class="form-check-label"
                                                    for="remember_me">{{ __('messages.remember_me') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100"
                                                type="submit">{{ __('messages.login') }}</button>
                                        </div>
                                    </form>

                                    <!-- Language Switcher -->
                                    <div class="language-switcher-footer">
                                        <hr class="my-3">
                                        <div class="d-flex justify-content-center align-items-center gap-3">
                                            <span class="text-muted small">{{ __('messages.language') }}:</span>
                                            <a href="{{ route('locale.switch', 'fr') }}"
                                                class="lang-flag-btn {{ app()->getLocale() == 'fr' ? 'active' : '' }}"
                                                title="Français">
                                                <img src="{{ url('assets/img/flags/fr.svg') }}" alt="Français">
                                            </a>
                                            <a href="{{ route('locale.switch', 'en') }}"
                                                class="lang-flag-btn {{ app()->getLocale() == 'en' ? 'active' : '' }}"
                                                title="English">
                                                <img src="{{ url('assets/img/flags/en.svg') }}" alt="English">
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- <div class="credits">
                                                                    Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                                                                </div> -->
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main><!-- End #main -->
@endsection
