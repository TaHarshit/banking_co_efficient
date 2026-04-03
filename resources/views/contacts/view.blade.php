@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.view_contact') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('managecontacts') }}">{{ __('messages.manage_contacts') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.view_contact') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    @include('partials.messages')
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.contact_details') }}
                                <a href="{{ route('managecontacts') }}"
                                    class="btn btn-secondary btn-sm float-end">{{ __('messages.back') }}</a>
                            </h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>{{ __('messages.name') }}:</strong> {{ $contact->name }}
                                </div>
                                <div class="col-md-6">
                                    <strong>{{ __('messages.email') }}:</strong> {{ $contact->email }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>{{ __('messages.date') }}:</strong>
                                    {{ $contact->created_at->format('d-m-Y H:i') }}
                                </div>
                                <div class="col-md-6">
                                    <strong>{{ __('messages.status') }}:</strong>
                                    @if ($contact->status == 'replied')
                                        <span class="badge bg-success">{{ __('messages.replied') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('messages.pending') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>{{ __('messages.subject') }}:</strong> {{ $contact->subject }}
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <strong>{{ __('messages.message') }}:</strong>
                                    <div class="p-3 border rounded bg-light mt-2">
                                        {{ $contact->message }}
                                    </div>
                                </div>
                            </div>

                            @if ($contact->status == 'replied')
                                <hr>
                                <h5 class="card-title">{{ __('messages.reply_details') }}</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>{{ __('messages.replied_by') }}:</strong>
                                        {{ $contact->repliedByUser ? $contact->repliedByUser->name : 'N/A' }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ __('messages.replied_at') }}:</strong>
                                        {{ $contact->replied_at ? \Carbon\Carbon::parse($contact->replied_at)->format('d-m-Y H:i') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <strong>{{ __('messages.reply') }}:</strong>
                                        <div class="p-3 border rounded bg-light mt-2">
                                            {{ $contact->reply }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <hr>
                                <h5 class="card-title">{{ __('messages.send_reply') }}</h5>
                                <form action="{{ route('replycontact') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $contact->id }}">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label for="reply"
                                                class="form-label">{{ __('messages.reply_message') }}</label>
                                            <textarea class="form-control" name="reply" id="reply" rows="5" required minlength="10"></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit"
                                            class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                                    </div>
                                </form>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
