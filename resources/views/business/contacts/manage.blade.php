@extends('layouts.business')

@section('title', __('messages.manage_contacts'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.manage_contacts') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('messages.manage_contacts') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.contact_requests') }}</h5>

                        @if (Session::has('message'))
                            <div class="alert alert-{{ Session::get('icon') == 'success' ? 'success' : 'danger' }} alert-dismissible fade show"
                                role="alert">
                                {{ Session::get('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.subject') }}</th>
                                        <th>{{ __('messages.message') }}</th>
                                        <th>{{ __('messages.date') }}</th>
                                        <th>{{ __('messages.status') }}</th>
                                        <th>{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $key => $val)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $val->name }}</td>
                                            <td>{{ $val->email }}</td>
                                            <td>{{ $val->subject }}</td>
                                            <td title="{{ $val->message }}">
                                                {{ Str::limit($val->message, 50) }}
                                            </td>
                                            <td>{{ $val->created_at->format('d M Y, h:i A') }}</td>
                                            <td>
                                                @if ($val->status == 'pending')
                                                    <span class="badge bg-warning">{{ __('messages.pending') }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ __('messages.replied') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('business.contacts.view', $val->id) }}"
                                                    class="btn btn-sm btn-info" title="{{ __('messages.view_details') }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('business.contacts.delete', $val->id) }}"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('{{ __('messages.are_you_sure') }}')"
                                                    title="{{ __('messages.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
