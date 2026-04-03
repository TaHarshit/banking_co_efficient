@extends('layouts.business')

@section('title', isset($data) ? __('messages.edit_employee') : __('messages.add_employee'))

@section('content')
    <div class="pagetitle">
        <h1>{{ isset($data) ? __('messages.edit_employee') : __('messages.add_employee') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('business.employees') }}">{{ __('messages.employees') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ isset($data) ? __('messages.edit') : __('messages.add') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                @include('partials.messages')
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            {{ isset($data) ? __('messages.edit_employee') : __('messages.add_employee') }}</h5>

                        <form
                            action="{{ isset($data) ? route('business.employees.update', $data->id) : route('business.employees.store') }}"
                            method="POST" class="row g-3">
                            @csrf

                            <div class="col-12">
                                <label for="name" class="form-label">{{ __('messages.name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                    value="{{ isset($data) ? $data->name : old('name') }}" required>
                                @if ($errors->has('name'))
                                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">{{ __('messages.email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="email"
                                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                                    value="{{ isset($data) ? $data->email : old('email') }}" required>
                                @if ($errors->has('email'))
                                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                @endif
                                <small class="text-muted">{{ __('messages.email_register_hint') }}</small>
                            </div>

                            <div class="col-md-6">
                                <label for="department" class="form-label">{{ __('messages.department') }}</label>
                                <input type="text" name="department"
                                    class="form-control {{ $errors->has('department') ? 'is-invalid' : '' }}"
                                    id="department" value="{{ isset($data) ? $data->department : old('department') }}">
                                @if ($errors->has('department'))
                                    <div class="invalid-feedback">{{ $errors->first('department') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('messages.phone') }}</label>
                                <input type="text" name="phone"
                                    class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" id="phone"
                                    value="{{ isset($data) ? $data->phone : old('phone') }}">
                                @if ($errors->has('phone'))
                                    <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($data) ? __('messages.update_employee') : __('messages.add_employee') }}
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
