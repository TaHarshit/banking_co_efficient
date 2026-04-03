@extends('layouts.app')
@section('pagewisestyle')
    <link rel="stylesheet" href="{{ url('public/assets/vendor/dropify/dropify.min.css') }}">
@endsection
@section('pagewisescript')
    <script src="{{ url('public/assets/vendor/dropify/dropify.min.js') }}"></script>
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
                <div class="col-lg-12">
                    <!-- @include('partials.messages') -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.edit_profile') }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('updateprofile') }}" method="POST"
                                enctype="multipart/form-data" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ $data->id }}" />
                                <div class="col-12 position-relative">
                                    <label for="name" class="form-label">{{ __('messages.name') }}</label>
                                    <input type="text" name="name"
                                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                        value="{{ isset($data) ? $data->name : old('name') }}">
                                    @if ($errors->has('name'))
                                        <div class="invalid-tooltip">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>
                                <div class="col-12 position-relative">
                                    <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                    <input type="text" name="email"
                                        class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                                        value="{{ isset($data) ? $data->email : old('email') }}">
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
                                {{-- <div class="col-12 position-relative">
									<label for="profile_image" class="form-label">Profile Image</label>
									<div class="{{ $errors->has('profile_image') ? 'is-invalid' : '' }}">
										<input type="file" name="profile_image" class="dropify" id="profile_image" data-default-file="{{isset($data) && Storage::exists('public/profile_image/'.$data->profile_image) ? asset('storage/app/public/profile_image/'.$data->profile_image) : ''}}">
									</div>
									<label class="pl-1 mt-1 col-md-12 col-lg-12">Image dimension should be : 591 X 591</label>
									@if ($errors->has('profile_image')) 
										<div class="invalid-tooltip">{{$errors->first('profile_image')}}</div>
									@endif
								</div> --}}
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
