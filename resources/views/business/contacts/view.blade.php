@extends('layouts.business')

@section('title', __('messages.view_contact'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.view_contact') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('business.contacts') }}">{{ __('messages.manage_contacts') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.view_contact') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.contact_details') }}</h5>

                        @if (Session::has('message'))
                            <div class="alert alert-{{ Session::get('icon') == 'success' ? 'success' : 'danger' }} alert-dismissible fade show"
                                role="alert">
                                {{ Session::get('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.name') }}</label>
                            <div class="col-sm-9">
                                {{ $data->name }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.email') }}</label>
                            <div class="col-sm-9">
                                {{ $data->email }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.subject') }}</label>
                            <div class="col-sm-9">
                                {{ $data->subject }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.message') }}</label>
                            <div class="col-sm-9">
                                <div class="p-3 bg-light border rounded">
                                    {{ $data->message }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.date') }}</label>
                            <div class="col-sm-9">
                                {{ $data->created_at->format('d M Y, h:i A') }}
                            </div>
                        </div>

                        <hr>

                        @if ($data->status == 'replied')
                            <h5 class="card-title text-success">{{ __('messages.reply_details') }}</h5>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.replied_at') }}</label>
                                <div class="col-sm-9">
                                    {{ \Carbon\Carbon::parse($data->replied_at)->format('d M Y, h:i A') }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">{{ __('messages.reply_message') }}</label>
                                <div class="col-sm-9">
                                    <div class="p-3 bg-light border rounded border-success">
                                        {{ $data->reply }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <h5 class="card-title">{{ __('messages.send_reply') }}</h5>
                            <form action="{{ route('business.contacts.reply', $data->id) }}" method="POST">
                                @csrf
                                <div class="row mb-3">
                                    <label for="reply"
                                        class="col-sm-3 col-form-label fw-bold">{{ __('messages.reply_message') }}</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" name="reply" id="reply" rows="5" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit"
                                            class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="text-center mt-4">
                            <a href="{{ route('business.contacts') }}"
                                class="btn btn-secondary">{{ __('messages.back') }}</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
