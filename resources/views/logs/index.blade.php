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
            <h1>Admin Activity Logs</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="row g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="moduleFilter" class="form-label mb-0 fw-bold">Module</label>
                                        <select name="module" id="moduleFilter" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Modules</option>
                                            @foreach ($modules as $module)
                                                <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                                    {{ $module }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="adminFilter" class="form-label mb-0 fw-bold">Admin</label>
                                        <select name="admin_id" id="adminFilter" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Admins</option>
                                            @foreach ($admins as $admin)
                                                <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                                                    {{ $admin->name }} {{ $admin->surname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </form>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Admin</th>
                                            <th>Module</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>IP Address</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($logs as $log)
                                            <tr>
                                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                                <td>
                                                    @if($log->admin)
                                                        {{ $log->admin->name }} {{ $log->admin->surname }}
                                                    @else
                                                        <span class="text-muted">System/Deleted</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-primary">{{ $log->module }}</span></td>
                                                <td>
                                                    @php
                                                        $badgeClass = 'bg-secondary';
                                                        if(strtolower($log->action) == 'add' || strtolower($log->action) == 'create') $badgeClass = 'bg-success';
                                                        elseif(strtolower($log->action) == 'delete') $badgeClass = 'bg-danger';
                                                        elseif(strtolower($log->action) == 'update') $badgeClass = 'bg-warning text-dark';
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $log->action }}</span>
                                                </td>
                                                <td>{{ $log->description }}</td>
                                                <td><code>{{ $log->ip_address }}</code></td>
                                                <td>
                                                    @if($log->data)
                                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#dataModal{{ $log->id }}">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        
                                                        <!-- Modal -->
                                                        <div class="modal fade" id="dataModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Log Details #{{ $log->id }}</h5>
                                                                        <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#dataModal{{ $log->id }}" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <pre class="bg-light p-3"><code>{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</code></pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">No activity logs found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-center">
                                    {{ $logs->appends(request()->query())->links() }}
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
