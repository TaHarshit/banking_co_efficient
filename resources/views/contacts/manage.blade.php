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
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-2">
                                <h5 class="card-title p-0 m-0">{{ __('messages.manage_contacts') }}</h5>
                                <form method="GET" action="{{ route('managecontacts') }}" class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0 fw-bold text-nowrap">{{ __('messages.routing') ?? 'Routing' }}:</label>
                                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="all" {{ ($selectedFilter ?? '') == 'all' ? 'selected' : '' }}>
                                            {{ __('messages.all_requests') ?? 'All Requests' }}
                                        </option>
                                        <option value="central" {{ ($selectedFilter ?? '') == 'central' ? 'selected' : '' }}>
                                            {{ __('messages.central_support_only') ?? 'Central Support Only' }}
                                        </option>
                                        @if(!empty($businesses) && $businesses->count() > 0)
                                            <optgroup label="{{ __('messages.banks_businesses') ?? 'Banks / Tenants' }}">
                                                @foreach($businesses as $b)
                                                    <option value="{{ $b->id }}" {{ ($selectedFilter ?? '') == $b->id ? 'selected' : '' }}>
                                                        {{ $b->name }} ({{ $b->business_code }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                    @if(!empty($selectedFilter) && $selectedFilter !== 'all')
                                        <a href="{{ route('managecontacts') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                                            <i class="bi bi-x-circle"></i> {{ __('messages.clear') ?? 'Clear' }}
                                        </a>
                                    @endif
                                </form>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table datatable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.name') }}</th>
                                                    <th>{{ __('messages.routing') ?? 'Routing' }}</th>
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
                                                        <td>
                                                            @if($val->business)
                                                                <span class="badge bg-primary" title="Bank ID: {{ $val->business_id }}">
                                                                    {{ $val->business->name }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">
                                                                    {{ __('messages.central_support') ?? 'Central Support' }}
                                                                </span>
                                                            @endif
                                                        </td>
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
