@extends('layouts.app')
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    <main>
        <div class="container">
            <section class="section error-404 min-vh-100 d-flex flex-column align-items-center justify-content-center">
                <h1>500</h1>
                <h2 class="text-center">Internal server error! <br>{{ $message }}</h2>
                <a class="btn" href="{{ route('dashboard') }}">Back to home</a>
                <img src="{{ url('assets/img/not-found.svg') }}" class="img-fluid py-5" alt="Page Not Found">
                <div class="credits">
                    Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                </div>
            </section>
        </div>
    </main><!-- End #main -->
@endsection
