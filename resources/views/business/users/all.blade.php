@extends('layouts.business')

@section('title', __('messages.all_users'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.all_users') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('messages.users') }}</li>
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
                            <h5 class="card-title mb-0">{{ __('messages.registered_users') }}</h5>
                            <a href="{{ route('business.users.pending') }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-hourglass-split"></i> {{ __('messages.pending_requests') }}
                                @php
                                    $pendingCount = $users->where('status', 'pending')->count();
                                @endphp
                                @if ($pendingCount > 0)
                                    <span class="badge bg-danger">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            {{ __('messages.your_business_code') }}: <strong>{{ $business->business_code }}</strong>
                            - {{ __('messages.your_business_code_share') }}
                        </div>

                        <table class="table table-bordered datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('messages.email') }}</th>
                                    <th>{{ __('messages.phone') }}</th>
                                    <th>{{ __('messages.status') }}</th>
                                    <th>{{ __('messages.joined') }}</th>
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
                                        <td>
                                            @if (in_array($user->status, [1, '1', 'active'], false))
                                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                                            @elseif(in_array($user->status, [2, '2', 'pending'], false))
                                                <span class="badge bg-warning">{{ __('messages.pending') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('messages.rejected') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            @if (in_array($user->status, [2, '2', 'pending'], false))
                                                <a href="{{ route('business.users.approve', $user->id) }}"
                                                    class="btn btn-success btn-sm" title="{{ __('messages.approve') }}">
                                                    <i class="bi bi-check"></i>
                                                </a>
                                                <a href="{{ route('business.users.reject', $user->id) }}"
                                                    class="btn btn-danger btn-sm" title="{{ __('messages.reject') }}">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                            @else
                                                <a href="javascript:void(0)"
                                                    onclick="showConfirmToast('{{ route('business.users.remove', $user->id) }}')"
                                                    class="btn btn-outline-danger btn-sm"
                                                    title="{{ __('messages.remove_from_business') }}">
                                                    <i class="bi bi-person-x"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.confirm_remove') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('messages.confirm_remove_user') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <a href="#" id="confirm_url" class="btn btn-danger">{{ __('messages.remove') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('customjs')
    <script>
        function showConfirmToast(url) {
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
@endsection
