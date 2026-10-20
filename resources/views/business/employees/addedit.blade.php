@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_employee') : __('messages.add_employee'))

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ isset($data) ? __('messages.edit_employee') : __('messages.add_employee') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('business.employees') }}">{{ __('messages.employees') }}</a></li>
                <li class="breadcrumb-item active">{{ isset($data) ? __('messages.edit') : __('messages.add') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-10">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            {{ isset($data) ? __('messages.edit_employee') : __('messages.add_employee') }}</h5>

                        <form
                            action="{{ isset($data) ? route('business.employees.update', $data->id) : route('business.employees.store') }}"
                            method="POST" class="row g-3 needs-validation" novalidate>
                            @csrf

                            <div class="col-md-6 position-relative">
                                <label for="name" class="form-label fw-bold">{{ __('messages.name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                    value="{{ isset($data) ? $data->name : old('name') }}" placeholder="e.g. John Doe" required>
                                @if ($errors->has('name'))
                                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6 position-relative">
                                <label for="email" class="form-label fw-bold">{{ __('messages.email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="email"
                                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                                    value="{{ isset($data) ? $data->email : old('email') }}" placeholder="e.g. john@example.com" required>
                                @if ($errors->has('email'))
                                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                @endif
                                <small class="text-muted">{{ __('messages.email_register_hint') }}</small>
                            </div>

                            <div class="col-md-6 position-relative">
                                <label for="department" class="form-label fw-bold">{{ __('messages.department') }}</label>
                                <input type="text" name="department"
                                    class="form-control {{ $errors->has('department') ? 'is-invalid' : '' }}"
                                    id="department" value="{{ isset($data) ? $data->department : old('department') }}" placeholder="e.g. Sales / Marketing">
                                @if ($errors->has('department'))
                                    <div class="invalid-feedback">{{ $errors->first('department') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6 position-relative">
                                <label for="phone" class="form-label fw-bold">{{ __('messages.phone_number') }}</label>
                                <input type="text" name="phone"
                                    class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" id="phone"
                                    value="{{ isset($data) ? $data->phone : old('phone') }}" placeholder="e.g. +1 234 567 8900">
                                @if ($errors->has('phone'))
                                    <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                                @endif
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($data) ? __('messages.save') : __('messages.add') }}
                                </button>
                                <a href="{{ route('business.employees') }}"
                                    class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
