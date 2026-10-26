@extends('layouts.business')

@section('title', __('messages.activity_logs') ?? 'Activity Logs')

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.activity_logs') ?? 'Activity Logs' }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.activity_logs') ?? 'Activity Logs' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="m-0">{{ __('messages.activity_logs') ?? 'Activity Logs' }}</h5>
                            <form method="GET" action="{{ route('business.activity_logs.index') }}" class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <label for="moduleFilter" class="form-label mb-0 fw-bold me-2">{{ __('messages.module') ?? 'Module' }}:</label>
                                </div>
                                <div class="col-auto">
                                    <select name="module" id="moduleFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">{{ __('messages.all_modules') ?? 'All Modules' }}</option>
                                        @foreach ($modules as $mod)
                                            <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>
                                                {{ $mod }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(request('module'))
                                    <div class="col-auto">
                                        <a href="{{ route('business.activity_logs.index') }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> {{ __('messages.clear') ?? 'Clear' }}
                                        </a>
                                    </div>
                                @endif
                            </form>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.date_time') ?? 'Date & Time' }}</th>
                                        <th>{{ __('messages.action_by') ?? 'Performed By' }}</th>
                                        <th>{{ __('messages.module') ?? 'Module' }}</th>
                                        <th>{{ __('messages.action') ?? 'Action' }}</th>
                                        <th>{{ __('messages.description') ?? 'Description' }}</th>
                                        <th>{{ __('messages.ip_address') ?? 'IP Address' }}</th>
                                        <th class="text-center">{{ __('messages.details') ?? 'Details' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $log)
                                        <tr>
                                            <td style="white-space: nowrap;">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                <strong>{{ $log->actor_name ?? auth()->guard('business')->user()->name }}</strong>
                                            </td>
                                            <td><span class="badge bg-primary">{{ $log->module }}</span></td>
                                            <td>
                                                @php
                                                    $act = strtolower($log->action);
                                                    $badgeClass = 'bg-secondary';
                                                    if (in_array($act, ['add', 'create', 'approve', 'import'])) {
                                                        $badgeClass = 'bg-success';
                                                    } elseif (in_array($act, ['delete', 'remove', 'reject'])) {
                                                        $badgeClass = 'bg-danger';
                                                    } elseif (in_array($act, ['update', 'edit', 'status change', 'order update'])) {
                                                        $badgeClass = 'bg-warning text-dark';
                                                    }
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $log->action }}</span>
                                            </td>
                                            <td>{{ $log->description }}</td>
                                            <td><code>{{ $log->ip_address }}</code></td>
                                            <td class="text-center">
                                                @if($log->data)
                                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#dataModal{{ $log->id }}">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Modal -->
                                                    <div class="modal fade" id="dataModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg text-start">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">{{ __('messages.log_details') ?? 'Log Details' }} #{{ $log->id }} ({{ $log->module }} - {{ $log->action }})</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="bi bi-clock-history d-block fs-3 mb-2"></i>
                                                {{ __('messages.no_activity_logs') ?? 'No activity logs found.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $logs->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
