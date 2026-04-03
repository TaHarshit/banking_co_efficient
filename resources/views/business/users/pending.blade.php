@extends('layouts.business')

@section('title', __('messages.pending_users'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.pending_user_requests') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">{{ __('messages.users_waiting_approval') }}</h5>
                            <a href="{{ route('business.users') }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-people"></i> {{ __('messages.view_all_users') }}
                            </a>
                        </div>

                        @if ($users->count() > 0)
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.phone') }}</th>
                                        <th>{{ __('messages.requested_on') }}</th>
                                        <th>{{ __('messages.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i = 1; @endphp
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->phone_no ?? '-' }}</td>
                                            <td>{{ $user->created_at->format('d M Y, h:i A') }}</td>
                                            <td>
                                                <a href="{{ route('business.users.approve', $user->id) }}"
                                                    class="btn btn-success btn-sm" title="{{ __('messages.approve') }}">
                                                    <i class="bi bi-check-circle"></i> {{ __('messages.approve') }}
                                                </a>
                                                <a href="{{ route('business.users.reject', $user->id) }}"
                                                    class="btn btn-danger btn-sm" title="{{ __('messages.reject') }}">
                                                    <i class="bi bi-x-circle"></i> {{ __('messages.reject') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> {{ __('messages.no_pending_requests') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
