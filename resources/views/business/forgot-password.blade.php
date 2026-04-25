<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Forgot Password - {{ config('app.name') }}</title>
    <link href="{{ url('public/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('public/assets/css/style.css') }}" rel="stylesheet">
</head>

<style>
        :root {
            --primary: {{ env('APP_THEME_COLOR', '#4154f1') }};
            --hov-primary: {{ env('APP_HOVER_THEME_COLOR', '#012970') }};
        }
    </style>

<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2 text-center">
                                        <h5 class="card-title pb-0 fs-4">Forgot Password</h5>
                                        <p class="text-center small">Enter your business email to receive a reset link</p>
                                    </div>

                                    @if (Session::has('message'))
                                        <div class="alert alert-{{ Session::get('icon') == 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                                            {{ Session::get('message') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('business.password.email') }}">
                                        @csrf
                                        <div class="col-12 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email" value="{{ old('email') }}" required>
                                            @if ($errors->has('email'))
                                                <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                            @endif
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
                                        </div>
                                    </form>

                                    <div class="mt-3 text-center">
                                        <a href="{{ route('business.login') }}">Back to login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script src="{{ url('public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
