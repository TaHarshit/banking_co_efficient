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
            <h1>{{ isset($data) ? 'Edit Business' : 'Add Business' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('managebusinesses') }}">Manage Businesses</a></li>
                    <li class="breadcrumb-item">{{ isset($data) ? 'Edit Business' : 'Add Business' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ isset($data) ? 'Edit Business' : 'Add Business' }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('storebusiness') }}" method="POST"
                                enctype="multipart/form-data" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : '0' }}">

                                <div class="col-12 position-relative">
                                    <label for="name" class="form-label">Business Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                        value="{{ isset($data) ? $data->name : old('name') }}">
                                    @if ($errors->has('name'))
                                        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                                        value="{{ isset($data) ? $data->email : old('email') }}">
                                    @if ($errors->has('email'))
                                        <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                    @endif
                                    @if (!isset($data))
                                        <small class="text-muted">An invitation email will be sent to this address to set up
                                            password.</small>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="logo" class="form-label">Logo</label>
                                    <div class="{{ $errors->has('logo') ? 'is-invalid' : '' }}">
                                        <input type="file" name="logo"
                                            class="dropify {{ $errors->has('logo') ? 'is-invalid' : '' }}" id="logo"
                                            data-default-file="{{ isset($data) && $data->logo && Storage::exists('business_logos/' . $data->logo) ? asset('storage/app/public/business_logos/' . $data->logo) : '' }}">
                                    </div>
                                    <label class="pl-1 mt-1 col-md-12 col-lg-12">Recommended size: 200x200px (JPG,
                                        PNG)</label>
                                    @if ($errors->has('logo'))
                                        <div class="invalid-feedback">{{ $errors->first('logo') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea name="address" id="address" rows="3"
                                        class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}">{{ isset($data) ? $data->address : old('address') }}</textarea>
                                    @if ($errors->has('address'))
                                        <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                                    @endif
                                </div>

                                @if (isset($data))
                                    <div class="col-12 position-relative">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status"
                                            class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}">
                                            <option value="1"
                                                {{ isset($data) && $data->status == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0"
                                                {{ isset($data) && $data->status == 0 ? 'selected' : '' }}>Inactive
                                            </option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                                        @endif
                                    </div>
                                @endif

                                <div class="col-sm-10">
                                    <button type="submit"
                                        class="btn btn-primary">{{ isset($data) ? 'Save' : 'Add Business' }}</button>
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
