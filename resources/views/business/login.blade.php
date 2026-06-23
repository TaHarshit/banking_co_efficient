<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ __('messages.business_login') }} - {{ config('app.name') }}</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <link href="{{ url('assets/img/favicon.png') }}" rel="icon">
    <link href="{{ url('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">
    <link href="{{ url('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ url('assets/css/style.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary: {{ env('APP_THEME_COLOR', '#4154f1') }};
            --hov-primary: {{ env('APP_HOVER_THEME_COLOR', '#012970') }};
        }
    </style>
    <style>
        .btn-primary {
            background-color: #4154f1;
            border-color: #4154f1;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #3145d9;
            border-color: #3145d9;
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
</head>

<body>
    <main>
        <div class="container">
            <section
                class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="d-flex justify-content-center py-4">
                                <a href="#" class="logo d-flex align-items-center w-auto">
                                    <span class="d-none d-lg-block">{{ __('messages.business_login') }}</span>
                                </a>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">
                                            {{ __('messages.business_login_title') }}</h5>
                                        <p class="text-center small">{{ __('messages.business_login_subtitle') }}</p>
                                    </div>
                                    @if (Session::has('message'))
                                        <div class="alert alert-{{ Session::get('icon') == 'success' ? 'success' : 'danger' }} alert-dismissible fade show"
                                            role="alert">
                                            {{ Session::get('message') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                    @endif
                                    <form class="row g-3 needs-validation" method="POST"
                                        action="{{ route('business.login.submit') }}" novalidate>
                                        @csrf
                                        <div class="col-12">
                                            <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="email" name="email"
                                                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                                    id="email" value="{{ old('email') }}" required>
                                                @if ($errors->has('email'))
                                                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                                @else
                                                    <div class="invalid-feedback">
                                                        {{ __('messages.please_enter_email') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="password"
                                                class="form-label">{{ __('messages.password') }}</label>
                                            <input type="password" name="password"
                                                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                                id="password" required>
                                            @if ($errors->has('password'))
                                                <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                                            @else
                                                <div class="invalid-feedback">
                                                    {{ __('messages.please_enter_password') }}</div>
                                            @endif
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember_me"
                                                    value="1" id="rememberMe">
                                                <label class="form-check-label"
                                                    for="rememberMe">{{ __('messages.remember_me') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <a href="{{ route('business.password.email') }}"
                                                class="text-decoration-underline small float-end">{{ __('messages.forgot_password') }}</a>
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
                                            <a href="{{ route('business.locale.switch', 'fr') }}"
                                                class="lang-flag-btn {{ app()->getLocale() == 'fr' ? 'active' : '' }}"
                                                title="Français">
                                                <img src="{{ url('assets/img/flags/fr.svg') }}" alt="Français">
                                            </a>
                                            <a href="{{ route('business.locale.switch', 'en') }}"
                                                class="lang-flag-btn {{ app()->getLocale() == 'en' ? 'active' : '' }}"
                                                title="English">
                                                <img src="{{ url('assets/img/flags/en.svg') }}" alt="English">
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script src="{{ url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/js/main.js') }}"></script>
</body>

</html>
