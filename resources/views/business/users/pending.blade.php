@extends('layouts.business')

@section('title', __('messages.pending_users'))

@section('pagewisestyle')
@endsection

@section('pagewisescript')
@endsection

@section('customjs')
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.pending_user_requests') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('business.users') }}">{{ __('messages.users') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.pending_users') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.users_waiting_approval') }}
                            <div style="float: right;">
                                <a href="{{ route('business.users') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> {{ __('messages.view_all_users') }}
                                </a>
                            </div>
                        </h5>

                        <div class="row">
                            <div class="col-12">
                                @if ($users->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.phone_number') }}</th>
                                                    <th>{{ __('messages.requested_on') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $i = 1; @endphp
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <td>{{ $i++ }}</td>
                                                        <td><strong>{{ $user->name . ($user->surname ? ' ' . $user->surname : '') }}</strong></td>
                                                        <td>{{ $user->email }}</td>
                                                        <td>{{ $user->phone_no ?? '-' }}</td>
                                                        <td>{{ $user->created_at ? $user->created_at->format('d M Y, h:i A') : '-' }}</td>
                                                        <td>
                                                            <a href="{{ route('business.users.approve', $user->id) }}"
                                                                style="font-size: 20px; color:#2eca6a !important;"
                                                                class="text-success me-2"
                                                                title="{{ __('messages.approve') }}">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </a>
                                                            <a href="{{ route('business.users.reject', $user->id) }}"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                class="text-danger"
                                                                title="{{ __('messages.reject') }}">
                                                                <i class="bi bi-x-circle-fill"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> {{ __('messages.no_pending_requests') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
