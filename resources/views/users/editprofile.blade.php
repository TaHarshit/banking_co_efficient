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
            <h1>{{ __('messages.edit_profile') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.edit_profile') }}</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.edit_profile') }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('updateprofile') }}" method="POST"
                                enctype="multipart/form-data" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ $data->id }}" />

                                <div class="col-md-6">
                                    <label for="name" class="form-label">{{ __('messages.name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" id="name"
                                        value="{{ isset($data) ? $data->name : old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="surname" class="form-label">{{ __('messages.surname') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="surname"
                                        class="form-control @error('surname') is-invalid @enderror" id="surname"
                                        value="{{ isset($data) ? $data->surname : old('surname') }}" required>
                                    @error('surname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">{{ __('messages.email') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror" id="email"
                                        value="{{ isset($data) ? $data->email : old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" name="username"
                                        class="form-control @error('username') is-invalid @enderror" id="username"
                                        value="{{ isset($data) ? $data->username : old('username') }}">
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="phone_no" class="form-label">{{ __('messages.phone_number') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone_no"
                                        class="form-control @error('phone_no') is-invalid @enderror" id="phone_no"
                                        value="{{ isset($data) ? $data->phone_no : old('phone_no') }}" required>
                                    @error('phone_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="job_title" class="form-label">Job Title</label>
                                    <input type="text" name="job_title"
                                        class="form-control @error('job_title') is-invalid @enderror" id="job_title"
                                        value="{{ isset($data) ? $data->job_title : old('job_title') }}">
                                    @error('job_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="institution" class="form-label">Institution</label>
                                    <input type="text" name="institution"
                                        class="form-control @error('institution') is-invalid @enderror" id="institution"
                                        value="{{ isset($data) ? $data->institution : old('institution') }}">
                                    @error('institution')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" name="department"
                                        class="form-control @error('department') is-invalid @enderror" id="department"
                                        value="{{ isset($data) ? $data->department : old('department') }}">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="year_of_experience" class="form-label">Years of Experience</label>
                                    <input type="text" name="year_of_experience"
                                        class="form-control @error('year_of_experience') is-invalid @enderror"
                                        id="year_of_experience"
                                        value="{{ isset($data) ? $data->year_of_experience : old('year_of_experience') }}">
                                    @error('year_of_experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label">{{ __('messages.password') }}</label>
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror" id="password">
                                    <small class="text-muted">Leave blank to keep current password.</small>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- <div class="col-12 position-relative">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <div class="{{ $errors->has('profile_image') ? 'is-invalid' : '' }}">
                                        <input type="file" name="profile_image" class="dropify" id="profile_image" data-default-file="{{isset($data) && Storage::exists('profile_image/'.$data->profile_image) ? asset('storage/app/public/profile_image/'.$data->profile_image) : ''}}">
                                    </div>
                                    <label class="pl-1 mt-1 col-md-12 col-lg-12">Image dimension should be : 591 X 591</label>
                                    @if ($errors->has('profile_image')) 
                                        <div class="invalid-tooltip">{{$errors->first('profile_image')}}</div>
                                    @endif
                                </div> --}}

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i>
                                        {{ __('messages.save') }}
                                    </button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-light ms-2">Cancel</a>
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
