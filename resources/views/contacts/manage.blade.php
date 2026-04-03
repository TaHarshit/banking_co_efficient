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
            <h1>{{ __('messages.manage_contacts') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('messages.manage_contacts') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    @include('partials.messages')
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.manage_contacts') }}
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.company') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.subject') }}</th>
                                                    <th>{{ __('messages.message') }}</th>
                                                    <th>{{ __('messages.date') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($contacts as $val)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ $val->name }}</td>
                                                        <td>{{ $val->business->name ?? __('messages.na') }}</td>
                                                        <td>{{ $val->email }}</td>
                                                        <td>{{ $val->subject }}</td>
                                                        <td title="{{ $val->message }}">
                                                            {{ Str::limit($val->message, 50) }}</td>
                                                        <td>{{ $val->created_at->format('d-m-Y H:i') }}</td>
                                                        <td>
                                                            @if ($val->status == 'replied')
                                                                <span
                                                                    class="badge bg-success">{{ __('messages.replied') }}</span>
                                                            @else
                                                                <span
                                                                    class="badge bg-warning text-dark">{{ __('messages.pending') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('viewcontact', ['id' => $val->id]) }}"
                                                                style="font-size: 20px;" class="text-primary me-2">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </a>
                                                            <a href="javascript:void(0);"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                onclick="return showConfirmToast('{{ route('deletecontact', ['id' => $val->id]) }}')"
                                                                class="text-danger"><i class="bi bi-trash-fill"></i></a>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
