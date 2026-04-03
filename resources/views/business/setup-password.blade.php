<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ __('messages.setup_password') }} - {{ config('app.name') }}</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <link href="{{ url('public/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ url('public/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">
    <link href="{{ url('public/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('public/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ url('public/assets/css/style.css') }}" rel="stylesheet">
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
    </style>
</head>

<body>
    <main>
        <div class="container">
            <section
                class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="d-flex justify-content-center py-4">
                                <a href="#" class="logo d-flex align-items-center w-auto">
                                    <span class="d-none d-lg-block">{{ __('messages.setup_your_password') }}</span>
                                </a>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">{{ __('messages.welcome') }},
                                            {{ $business->name }}!
                                        </h5>
                                        <p class="text-center small">{{ __('messages.setup_password_subtitle') }}</p>
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
                                        action="{{ route('business.password.setup.submit') }}" novalidate>
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $token }}">

                                        <div class="col-12">
                                            <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                            <input type="email" class="form-control" id="email"
                                                value="{{ $business->email }}" disabled>
                                            <small
                                                class="text-muted">{{ __('messages.this_is_your_business_email') }}</small>
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
                                                    {{ __('messages.please_enter_password_min') }}</div>
                                            @endif
                                            <small class="text-muted">{{ __('messages.password_min_chars') }}</small>
                                        </div>

                                        <div class="col-12">
                                            <label for="password_confirmation"
                                                class="form-label">{{ __('messages.confirm_password') }}</label>
                                            <input type="password" name="password_confirmation"
                                                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                                id="password_confirmation" required>
                                            <div class="invalid-feedback">{{ __('messages.please_confirm_password') }}
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100"
                                                type="submit">{{ __('messages.set_password_continue') }}</button>
                                        </div>

                                        <div class="col-12">
                                            <p class="small mb-0 text-center">
                                                <a
                                                    href="{{ route('business.login') }}">{{ __('messages.already_have_password') }}</a>
                                            </p>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script src="{{ url('public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('public/assets/js/main.js') }}"></script>
</body>

</html>
