@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        function togglePasswordVisibility(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ isset($data) ? __('messages.edit_user') : __('messages.add_user') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manageusers') }}">{{ __('messages.manage_users') }}</a>
                    </li>
                    <li class="breadcrumb-item">{{ isset($data) ? __('messages.edit_user') : __('messages.add_user') }}</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ isset($data) ? __('messages.edit_user') : __('messages.add_user') }}</h5>
                            <form class="row g-3 needs-validation"
                                action="{{ route('storeuser', ['id' => isset($data) ? $data->id : '0']) }}"
                                method="POST" enctype="multipart/form-data" novalidate>
                                @csrf
                                
                                <div class="col-md-6">
                                    <label for="name" class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name"
                                        value="{{ isset($data) ? $data->name : old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="surname" class="form-label">{{ __('messages.surname') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="surname" class="form-control @error('surname') is-invalid @enderror" id="surname"
                                        value="{{ isset($data) ? $data->surname : old('surname') }}" required>
                                    @error('surname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">{{ __('messages.email') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                        value="{{ isset($data) ? $data->email : old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" id="username"
                                        value="{{ isset($data) ? $data->username : old('username') }}">
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="phone_no" class="form-label">{{ __('messages.phone_number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone_no" class="form-control @error('phone_no') is-invalid @enderror" id="phone_no"
                                        value="{{ isset($data) ? $data->phone_no : old('phone_no') }}" required>
                                    @error('phone_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="business_id" class="form-label">Select Business</label>
                                    <select name="business_id" id="business_id" class="form-select @error('business_id') is-invalid @enderror">
                                        <option value="">No Business (Individual)</option>
                                        @foreach($businesses as $business)
                                            <option value="{{ $business->id }}" 
                                                {{ (isset($data) && $data->business_id == $business->id) || old('business_id') == $business->id ? 'selected' : '' }}>
                                                {{ $business->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="job_title" class="form-label">Job Title</label>
                                    <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror" id="job_title"
                                        value="{{ isset($data) ? $data->job_title : old('job_title') }}">
                                    @error('job_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="institution" class="form-label">Institution</label>
                                    <input type="text" name="institution" class="form-control @error('institution') is-invalid @enderror" id="institution"
                                        value="{{ isset($data) ? $data->institution : old('institution') }}">
                                    @error('institution')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" id="department"
                                        value="{{ isset($data) ? $data->department : old('department') }}">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="year_of_experience" class="form-label">Years of Experience</label>
                                    <input type="text" name="year_of_experience" class="form-control @error('year_of_experience') is-invalid @enderror" id="year_of_experience"
                                        value="{{ isset($data) ? $data->year_of_experience : old('year_of_experience') }}">
                                    @error('year_of_experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">{{ __('messages.status') }}</label>
                                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                         <option value="1" {{ (isset($data) && in_array($data->status, [1, '1', 'active'], false)) || old('status') === '1' || old('status') === 1 || (!isset($data) && old('status') === null) ? 'selected' : '' }}>
                                             {{ __('messages.active') }}</option>
                                         <option value="0" {{ (isset($data) && in_array($data->status, [0, '0', 'inactive', 'disabled', 'rejected'], false)) || old('status') === '0' || old('status') === 0 ? 'selected' : '' }}>
                                             {{ __('messages.inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if (isset($data))
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">{{ __('messages.password') }}</label>
                                        <div class="input-group">
                                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', 'passwordToggleIcon')">
                                                <i class="bi bi-eye-slash" id="passwordToggleIcon"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Leave blank to keep current password.</small>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @else
                                    <div class="col-12 mt-4">
                                        <div class="alert alert-info border-info d-flex align-items-center">
                                            <i class="bi bi-info-circle me-2 fs-4"></i>
                                            <div>
                                                Password will be automatically generated and sent to the user's email address.
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i>
                                        {{ isset($data) ? __('messages.save') : __('messages.add') }}
                                    </button>
                                    <a href="{{ route('manageusers') }}" class="btn btn-light ms-2">Cancel</a>
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
