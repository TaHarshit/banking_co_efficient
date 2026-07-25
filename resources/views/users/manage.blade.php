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
            $.post('{{ route('changeuserstatus') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    NotifMsg('User status updated successfully', 'success', 'Success');
                } else {
                    NotifMsg('Something went wrong', 'danger', 'Danger');
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
            <h1>{{ __('messages.manage_users') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ __('messages.manage_users') }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- @include('partials.messages') -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title d-flex justify-content-between align-items-center">
                                <span>{{ __('messages.manage_users') }}</span>
                                <form method="GET" action="{{ route('manageusers') }}" class="d-flex align-items-center"
                                    style="gap: 10px;">
                                    <label for="companyFilter" class="form-label mb-0 fw-bold">{{ __('messages.filter_by') }}</label>
                                    <select name="company" id="companyFilter" class="form-select w-auto"
                                        onchange="this.form.submit()">
                                        <option value="">{{ __('messages.all_users') }}</option>
                                        <option value="individual"
                                            {{ isset($current_filter) && $current_filter == 'individual' ? 'selected' : '' }}>
                                            {{ __('messages.individual_users') }}</option>
                                        @foreach ($businesses as $business)
                                            <option value="{{ $business->id }}"
                                                {{ isset($current_filter) && $current_filter == $business->id ? 'selected' : '' }}>
                                                {{ __('messages.company') }}: {{ $business->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('exportusers', ['company' => request('company')]) }}"
                                        class="btn btn-primary ms-2">
                                        <i class="bi bi-file-earmark-excel"></i> {{ __('messages.export') }}
                                    </a>
                                    <a href="{{ route('createuser') }}" class="btn btn-primary"><i
                                                class="bi bi-person-plus"></i> {{ __('messages.add_user') }}</a>
                                </form>
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr> 
                                                    <th>#</th>
                                                    <th>{{ __('messages.user_type') }}</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.email') }}</th>
                                                    <th>{{ __('messages.phone_number') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($users as $val)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>
                                                            @if (empty($val->business_id))
                                                                <span class="badge bg-secondary">{{ __('messages.individual') }}</span>
                                                            @else
                                                                <span class="badge bg-info text-dark">{{ __('messages.company') }}:
                                                                    {{ $val->business ? $val->business->name : __('messages.na') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $val->name . ' ' . $val->surname }}</td>
                                                        <td>{{ $val->email }}</td>
                                                        <td>{{ $val->phone_no }}</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input"
                                                                    onchange="update_status(this)" type="checkbox"
                                                                    {{ in_array($val->status, [1, '1', 'active'], false) ? 'checked' : '' }}
                                                                    value="{{ $val->id }}">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{route('updateuser',['id'=>$val->id])}}" style="font-size: 20px; color:#00ACEF !important" class="text-primary">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            <a href="javascript:void(0);"
                                                                style="font-size: 20px; color:#EE6C4D !important;"
                                                                onclick="return showConfirmToast('{{ route('deleteuser', ['id' => $val->id]) }}')"
                                                                class="text-danger"><i class="bi bi-trash-fill"></i></a>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                        {{-- <table>
                                            <tr>
                                                <td>
                                                    <a href="{{route('createuser')}}" class="btn btn-primary">Add New</a>&nbsp;&nbsp;
                                                </td>
                                            </tr>
                                        </table> --}}
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
