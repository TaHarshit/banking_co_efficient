@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
    <script type="text/javascript">
        function update_status(el) {
            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('changebusinessstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('{{ __('messages.business_status_updated') }}', 'success',
                        '{{ __('messages.success') }}');
                } else {
                    NotifMsg('{{ __('messages.something_went_wrong') }}', 'danger', '{{ __('messages.danger') }}');
                }
            });
        }
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ __('messages.manage_businesses') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.manage_businesses') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('messages.manage_businesses') }}
                                <a href="{{ route('createbusiness') }}" class="btn btn-primary"
                                    style="float: right;">{{ __('messages.add_new') }} {{ __('messages.business') }}</a>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.logo') }}</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.business_code') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.address') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($businesses as $val)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>
                                                            @if ($val->logo && Storage::exists('public/business_logos/' . $val->logo))
                                                                <img src="{{ url('public/business_logos/' . $val->logo) }}"
                                                                    height="40" width="40"
                                                                    style="object-fit: cover; border-radius: 4px;">
                                                            @else
                                                                <div
                                                                    style="width: 40px; height: 40px; background: #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                                    <i class="bi bi-building"></i>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td>{{ $val->name }}</td>
                                                        <td>
                                                            <code
                                                                class="text-primary">{{ $val->business_code ?? __('messages.na') }}</code>
                                                        </td>
                                                        <td>{{ $val->email }}</td>
                                                        <td>{{ $val->address ?? __('messages.na') }}</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input"
                                                                    onchange="update_status(this)" type="checkbox"
                                                                    {{ $val->status == 1 ? 'checked' : '' }}
                                                                    value="{{ $val->id }}">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('updatebusiness', ['id' => $val->id]) }}"
                                                                style="font-size: 20px; color:#00ACEF !important"
                                                                class="text-primary" title="Edit">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            @if (empty($val->password))
                                                                <a href="{{ route('resendbusinessinvitation', ['id' => $val->id]) }}"
                                                                    style="font-size: 20px; color:#FFA500 !important"
                                                                    class="text-warning" title="Resend Invitation">
                                                                    <i class="bi bi-envelope-fill"></i>
                                                                </a>
                                                            @endif
                                                            <a href="javascript:void(0);"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                onclick="return showConfirmToast('{{ route('deletebusiness', ['id' => $val->id]) }}')"
                                                                class="text-danger" title="Delete"><i
                                                                    class="bi bi-trash-fill"></i></a>
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
