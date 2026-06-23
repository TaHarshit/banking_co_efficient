@extends('layouts.business')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.business_dashboard') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">{{ __('messages.dashboard') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            @if ($business->logo && Storage::exists('public/business_logos/' . $business->logo))
                                <img src="{{ asset('storage/app/public/business_logos/' . $business->logo) }}" height="50"
                                    width="50" style="object-fit: cover; border-radius: 8px;" class="me-3">
                            @else
                                <div class="me-3"
                                    style="width: 50px; height: 50px; background: #4154f1; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.5rem;">
                                    {{ substr($business->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <h5 class="mb-0">{{ __('messages.welcome') }}, {{ $business->name }}! 🎉</h5>
                                <small class="text-muted">{{ __('messages.business_code') }}:
                                    <strong>{{ $business->business_code }}</strong></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <!-- Employees Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card sales-card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.total_employees') }}</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                style="background: #e0f7fa;">
                                <i class="bi bi-people" style="color: #00acc1; font-size: 1.5rem;"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalEmployees }}</h6>
                                <a href="{{ route('business.employees') }}"
                                    class="text-muted small">{{ __('messages.view_all') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Users Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card revenue-card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.total_users') }}</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                style="background: #e8f5e9;">
                                <i class="bi bi-person-check" style="color: #43a047; font-size: 1.5rem;"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalUsers }}</h6>
                                <a href="{{ route('business.users') }}"
                                    class="text-muted small">{{ __('messages.view_all') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Users Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card customers-card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.active_users') }}</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                style="background: #e3f2fd;">
                                <i class="bi bi-person-fill-check" style="color: #1976d2; font-size: 1.5rem;"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $activeUsers }}</h6>
                                <span class="text-success small pt-1 fw-bold">{{ __('messages.active') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Users Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.pending_users') }}</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                style="background: #fff3e0;">
                                <i class="bi bi-hourglass-split" style="color: #fb8c00; font-size: 1.5rem;"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $pendingUsers }}</h6>
                                @if ($pendingUsers > 0)
                                    <a href="{{ route('business.users.pending') }}"
                                        class="text-warning small">{{ __('messages.review_now') }}</a>
                                @else
                                    <span class="text-muted small">{{ __('messages.no_pending') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seats and Results -->
        <div class="row">
            <!-- Seats Info -->
            <div class="col-xxl-6 col-xl-12">
                <div class="row">
                    <!-- Total Seats Card -->
                    <div class="col-md-6">
                        <div class="card info-card">
                            <div class="card-body">
                                <h5 class="card-title">{{ __('messages.total_seats') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                        style="background: #ede7f6;">
                                        <i class="bi bi-grid-3x3-gap" style="color: #7e57c2; font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $totalSeats }}</h6>
                                        <span class="text-muted small text-nowrap">{{ __('messages.allocated') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seats Remaining Card -->
                    <div class="col-md-6">
                        <div class="card info-card">
                            <div class="card-body">
                                <h5 class="card-title">{{ __('messages.seats_remaining') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                                        style="background: #fce4ec;">
                                        <i class="bi bi-grid" style="color: #e91e63; font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $seatsRemaining }}</h6>
                                        @if ($seatsRemaining > 0)
                                            <span
                                                class="text-success small text-nowrap">{{ __('messages.available') }}</span>
                                        @else
                                            <span
                                                class="text-danger small text-nowrap">{{ __('messages.fully_booked') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Pie Chart -->
            <div class="col-xxl-6 col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">{{ __('messages.skill_assessment_results') }} <span>|
                                    {{ __('messages.employees_performance') }}</span></h5>
                            @if ((isset($exam_types) && count($exam_types) > 0) || (isset($exam_templates) && count($exam_templates) > 0))
                                <div class="d-flex align-items-center">
                                    @if (isset($exam_types) && count($exam_types) > 0)
                                        <label for="examTypeFilter"
                                            class="form-label me-2 mb-0">{{ __('messages.filter_by_exam_type') ?? 'Filter by Type' }}</label>
                                        <select id="examTypeFilter" class="form-select form-select-sm me-2"
                                            style="width: 200px;">
                                            <option value="">{{ __('messages.all_exam_types') ?? 'All Types' }}
                                            </option>
                                            @foreach ($exam_types as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ isset($selected_exam_type) && $selected_exam_type == $key ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if (isset($exam_templates) && count($exam_templates) > 0)
                                        <label for="examTemplateFilter"
                                            class="form-label me-2 mb-0">{{ __('messages.filter_by_exam') }}</label>
                                        <select id="examTemplateFilter" class="form-select form-select-sm"
                                            style="width: 250px;">
                                            <option value="">{{ __('messages.all_exams') }}</option>
                                            @foreach ($exam_templates as $id => $title)
                                                <option value="{{ $id }}"
                                                    {{ $selected_exam_template == $id ? 'selected' : '' }}>
                                                    {{ $title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div style="height: 300px; position: relative;">
                            <canvas id="resultPieChart"></canvas>
                        </div>
                        <script src="{{ url('assets/vendor/chart.js/chart.umd.js') }}"></script>
                        <script>
                            document.addEventListener("DOMContentLoaded", () => {
                                const ctx = document.getElementById('resultPieChart').getContext('2d');

                                const updateChart = (data) => {
                                    new Chart(ctx, {
                                        type: 'pie',
                                        data: {
                                            labels: ['0-50%', '51-70%', '71-90%', '91-100%'],
                                            datasets: [{
                                                label: 'User Count',
                                                data: [
                                                    data['0-50'],
                                                    data['51-70'],
                                                    data['71-90'],
                                                    data['91-100']
                                                ],
                                                backgroundColor: [
                                                    '#f44336', '#ff9800', '#2196f3', '#4caf50'
                                                ],
                                                hoverOffset: 15,
                                                borderWidth: 2,
                                                borderColor: '#fff'
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: {
                                                    position: 'right',
                                                    labels: {
                                                        usePointStyle: true,
                                                        padding: 20,
                                                        font: {
                                                            size: 12
                                                        }
                                                    }
                                                },
                                                tooltip: {
                                                    padding: 10,
                                                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                                    titleColor: '#333',
                                                    bodyColor: '#666',
                                                    borderColor: '#ddd',
                                                    borderWidth: 1,
                                                    callbacks: {
                                                        label: function(context) {
                                                            let label = context.label || '';
                                                            if (label) {
                                                                label += ': ';
                                                            }
                                                            const total = data['0-50'] + data['51-70'] + data['71-90'] +
                                                                data['91-100'];
                                                            label += context.raw + ' Users (' +
                                                                Math.round(context.raw / (total || 1) * 100) + '%)';
                                                            return label;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                };

                                // Initial chart render
                                updateChart({
                                    '0-50': {{ $exam_stats['0-50'] ?? 0 }},
                                    '51-70': {{ $exam_stats['51-70'] ?? 0 }},
                                    '71-90': {{ $exam_stats['71-90'] ?? 0 }},
                                    '91-100': {{ $exam_stats['91-100'] ?? 0 }}
                                });

                                // Handle exam filter change
                                const examFilter = document.getElementById('examTemplateFilter');
                                if (examFilter) {
                                    examFilter.addEventListener('change', function() {
                                        const examTemplateId = this.value;
                                        const url = new URL(window.location);
                                        if (examTemplateId) {
                                            url.searchParams.set('exam_template_id', examTemplateId);
                                        } else {
                                            url.searchParams.delete('exam_template_id');
                                        }
                                        window.location.href = url.toString();
                                    });
                                }
                                // Handle exam type filter change (reload and reset selected template)
                                const examTypeFilter = document.getElementById('examTypeFilter');
                                if (examTypeFilter) {
                                    examTypeFilter.addEventListener('change', function() {
                                        const examType = this.value;
                                        const url = new URL(window.location);
                                        if (examType) {
                                            url.searchParams.set('exam_type', examType);
                                        } else {
                                            url.searchParams.delete('exam_type');
                                        }
                                        url.searchParams.delete('exam_template_id');
                                        window.location.href = url.toString();
                                    });
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Employees -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">{{ __('messages.recent_employees') }}</h5>
                            <a href="{{ route('business.employees') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> {{ __('messages.view_all') }}
                            </a>
                        </div>
                        @if ($recentEmployees->count() > 0)
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.department') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentEmployees as $employee)
                                        <tr>
                                            <td>{{ $employee->name }}</td>
                                            <td>{{ $employee->email }}</td>
                                            <td>{{ $employee->department ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                                <p class="mt-2">{{ __('messages.no_employees_yet') }}</p>
                                <a href="{{ route('business.employees.create') }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> {{ __('messages.add_employee') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">{{ __('messages.recent_users') }}</h5>
                            <a href="{{ route('business.users') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> {{ __('messages.view_all') }}
                            </a>
                        </div>
                        @if ($recentUsers->count() > 0)
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if ($user->status == 1)
                                                    <span class="badge bg-success">{{ __('messages.active') }}</span>
                                                @elseif($user->status == 2)
                                                    <span class="badge bg-warning">{{ __('messages.pending') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('messages.rejected') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-person-check" style="font-size: 2rem;"></i>
                                <p class="mt-2">{{ __('messages.no_users_yet') }}</p>
                                <small>{{ __('messages.share_business_code') }}
                                    <strong>{{ $business->business_code }}</strong></small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.quick_actions') }}</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('business.employees.create') }}" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> {{ __('messages.add_employee') }}
                            </a>
                            <a href="{{ route('business.employees.import') }}" class="btn btn-outline-success">
                                <i class="bi bi-file-earmark-excel"></i> {{ __('messages.import_employees') }}
                            </a>
                            <a href="{{ route('business.profile') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-gear"></i> {{ __('messages.edit_profile') }}
                            </a>
                            @if ($pendingUsers > 0)
                                <a href="{{ route('business.users.pending') }}" class="btn btn-warning">
                                    <i class="bi bi-hourglass-split"></i> {{ __('messages.pending_requests') }}
                                    <span class="badge bg-danger">{{ $pendingUsers }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
