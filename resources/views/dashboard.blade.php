@extends('layouts.app')
@section('pagewisestyle')
    <!-- Page Wise Style Sheet -->
@endsection
@section('pagewisescript')
    <script src="{{ url('public/assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ url('public/assets/vendor/echarts/echarts.min.js') }}"></script>
@endsection
@section('customjs')
    {{-- <script type="text/javascript">
		document.addEventListener("DOMContentLoaded", () => {
			new ApexCharts(document.querySelector("#reportsChart"), {
				series: [{
					name: 'Sales',
					data: [31, 40, 28, 51, 42, 82, 56],
				}, {
					name: 'Revenue',
					data: [11, 32, 45, 32, 34, 52, 41]
				}, {
					name: 'Customers',
					data: [15, 11, 32, 18, 9, 24, 11]
				}],
				chart: {
					height: 350,
					type: 'area',
					toolbar: {
						show: false
					},
				},
				markers: {
					size: 4
				},
				colors: ['#4154f1', '#2eca6a', '#ff771d'],
				fill: {
					type: "gradient",
					gradient: {
						shadeIntensity: 1,
						opacityFrom: 0.3,
						opacityTo: 0.4,
						stops: [0, 90, 100]
					}
				},
				dataLabels: {
					enabled: false
				},
				stroke: {
					curve: 'smooth',	
					width: 2
				},
				xaxis: {
					type: 'datetime',
					categories: ["2018-09-19T00:00:00.000Z", "2018-09-19T01:30:00.000Z", "2018-09-19T02:30:00.000Z", "2018-09-19T03:30:00.000Z", "2018-09-19T04:30:00.000Z", "2018-09-19T05:30:00.000Z", "2018-09-19T06:30:00.000Z"]
				},
				tooltip: {
					x: {
						format: 'dd/MM/yy HH:mm'
					},
				}
			}).render();
		});

		document.addEventListener("DOMContentLoaded", () => {
			var budgetChart = echarts.init(document.querySelector("#budgetChart")).setOption({
				legend: {
					data: ['Allocated Budget', 'Actual Spending']
				},
				radar: {
					// shape: 'circle',
					indicator: [{
						name: 'Sales',
						max: 6500
					},
					{
						name: 'Administration',
						max: 16000
					},
					{
						name: 'Information Technology',
						max: 30000
					},
					{
						name: 'Customer Support',
						max: 38000
					},
					{
						name: 'Development',
						max: 52000
					},
					{
						name: 'Marketing',
						max: 25000
					}]
				},
				series: [{
					name: 'Budget vs spending',
					type: 'radar',
					data: [{
						value: [4200, 3000, 20000, 35000, 50000, 18000],
						name: 'Allocated Budget'
					},
					{
						value: [5000, 14000, 28000, 26000, 42000, 21000],
						name: 'Actual Spending'
					}]
				}]
			});
		});
		document.addEventListener("DOMContentLoaded", () => {
			echarts.init(document.querySelector("#trafficChart")).setOption({
				tooltip: {
					trigger: 'item'
				},
				legend: {
					top: '5%',
					left: 'center'
				},
				series: [{
					name: 'Access From',
					type: 'pie',
					radius: ['40%', '70%'],
					avoidLabelOverlap: false,
					label: {
						show: false,
						position: 'center'
					},
					emphasis: {
						label: {
							show: true,
							fontSize: '18',
							fontWeight: 'bold'
						}
					},
					labelLine: {
						show: false
					},
					data: [{
						value: 1048,
						name: 'Search Engine'
					},
					{
						value: 735,
						name: 'Direct'
					},
					{
						value: 580,
						name: 'Email'
					},
					{
						value: 484,
						name: 'Union Ads'
					},
					{
						value: 300,
						name: 'Video Ads'
					}]
				}]
			});
		});
	</script> --}}
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        @include('partials.messages')
        <div class="pagetitle">
            <h1>{{ __('messages.dashboard') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <!-- <li class="breadcrumb-item"><a href="index.html">Home</a></li> -->
                    <li class="breadcrumb-item active">{{ __('messages.dashboard') }}</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section dashboard">
            <div class="row">
                <!-- Left side columns -->
                <div class="col-lg-12">
                    <div class="row">
                        <!-- Sales Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.users') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ $user_count }}</h6>
                                            {{-- <span class="text-success small pt-1 fw-bold">12%</span> <span class="text-muted small pt-2 ps-1">increase</span> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Sales Card -->

                        <!-- Admin Card -->
                        {{-- <div class="col-xxl-4 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title">Admins</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($admin_count) ? $admin_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Admin Card --> --}}

                        <!-- Business Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card customers-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.businesses') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-briefcase"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($business_count) ? $business_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Business Card -->

                        <!-- Personalized Experience Questions Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.personalized_experience') }} {{ __('messages.questions') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-question-circle"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($personalized_question_count) ? $personalized_question_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Personalized Experience Questions Card -->

                        <!-- Exam Questions Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.skill_assessment') }} {{ __('messages.questions') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-ui-checks"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($exam_question_count) ? $exam_question_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Exam Questions Card -->

                        <!-- Case Study Questions Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.case_study_questions') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-journal-text"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($case_study_question_count) ? $case_study_question_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Case Study Questions Card -->

                        <!-- Contacts Card -->
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card customers-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.contact_inquiries') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-envelope"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ isset($contact_count) ? $contact_count : 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Contacts Card -->

                        <!-- Quick Actions Card -->
                        <div class="col-12">
                            <div class="card info-card customers-card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.quick_actions') }} <span>| {{ __('messages.dashboard') }}</span></h5>
                                    <div class="d-flex align-items-center mt-3 flex-wrap gap-2">
                                        <a href="{{ route('createuser') }}" class="btn btn-primary btn-sm"><i
                                                class="bi bi-person-plus"></i> {{ __('messages.add_user') }}</a>
                                        <a href="{{ route('manageusers') }}" class="btn btn-secondary btn-sm"><i
                                                class="bi bi-people"></i> {{ __('messages.manage_users') }}</a>
                                        <a href="{{ route('managebusinesses') }}" class="btn btn-success btn-sm"><i
                                                class="bi bi-briefcase"></i> {{ __('messages.manage_businesses') }}</a>
                                        <a href="{{ route('managesections') }}"
                                            class="btn btn-warning btn-sm text-white"><i class="bi bi-list-task"></i> {{ __('messages.manage_sections') }}</a>
                                        <a href="{{ route('managecontacts') }}" class="btn btn-info btn-sm text-white"><i
                                                class="bi bi-envelope"></i> {{ __('messages.view_contacts') }}</a>
                                        <a href="{{ route('setting') }}" class="btn btn-dark btn-sm text-white"><i
                                                class="bi bi-gear"></i> {{ __('messages.settings') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Quick Actions Card -->

                        {{-- <!-- Revenue Card -->
						<div class="col-xxl-4 col-md-6">
							<div class="card info-card revenue-card">
								<div class="filter">
									<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
									<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
										<li class="dropdown-header text-start"><h6>Filter</h6></li>
										<li><a class="dropdown-item" href="#">Today</a></li>
										<li><a class="dropdown-item" href="#">This Month</a></li>
										<li><a class="dropdown-item" href="#">This Year</a></li>
									</ul>
								</div>
								<div class="card-body">
									<h5 class="card-title">Revenue <span>| This Month</span></h5>
									<div class="d-flex align-items-center">
										<div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
											<i class="bi bi-currency-dollar"></i>
										</div>
										<div class="ps-3">
											<h6>$3,264</h6>
											<span class="text-success small pt-1 fw-bold">8%</span> <span class="text-muted small pt-2 ps-1">increase</span>
										</div>
									</div>
								</div>
							</div>
						</div><!-- End Revenue Card -->

						<!-- Customers Card -->
						<div class="col-xxl-4 col-xl-12">
							<div class="card info-card customers-card">
								<div class="filter">
									<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
									<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
										<li class="dropdown-header text-start"><h6>Filter</h6></li>
										<li><a class="dropdown-item" href="#">Today</a></li>
										<li><a class="dropdown-item" href="#">This Month</a></li>
										<li><a class="dropdown-item" href="#">This Year</a></li>
									</ul>
								</div>
								<div class="card-body">
									<h5 class="card-title">Customers <span>| This Year</span></h5>
									<div class="d-flex align-items-center">
										<div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
											<i class="bi bi-people"></i>
										</div>
										<div class="ps-3">
											<h6>1244</h6>
											<span class="text-danger small pt-1 fw-bold">12%</span> <span class="text-muted small pt-2 ps-1">decrease</span>
										</div>
									</div>
								</div>
							</div>
						</div><!-- End Customers Card --> --}}

                        <!-- Reports -->
                        {{-- <div class="col-12">
							<div class="card">
								<div class="filter">
									<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
									<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
										<li class="dropdown-header text-start"><h6>Filter</h6></li>
										<li><a class="dropdown-item" href="#">Today</a></li>
										<li><a class="dropdown-item" href="#">This Month</a></li>
										<li><a class="dropdown-item" href="#">This Year</a></li>
									</ul>
								</div>
								<div class="card-body">
									<h5 class="card-title">Reports <span>/Today</span></h5>
									<!-- Line Chart -->
									<div id="reportsChart"></div>
								</div>
							</div>
						</div><!-- End Reports --> --}}

                        <!-- Skill Assessment Results -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body pb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">{{ __('messages.skill_assessment_results') }} <span>| Cumulative</span></h5>
                                        @if(isset($exam_templates) && count($exam_templates) > 0)
                                            <div class="d-flex align-items-center">
                                                <label for="examTemplateFilter" class="form-label me-2 mb-0">{{ __('messages.filter_by_exam') }}</label>
                                                <select id="examTemplateFilter" class="form-select form-select-sm" style="width: 250px;">
                                                    <option value="">{{ __('messages.all_exams') }}</option>
                                                    @foreach($exam_templates as $id => $title)
                                                        <option value="{{ $id }}" {{ $selected_exam_template == $id ? 'selected' : '' }}>
                                                            {{ $title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                    <div id="resultPieChart" style="min-height: 400px;" class="echart"></div>
                                    <script>
                                        document.addEventListener("DOMContentLoaded", () => {
                                            const chartInstance = echarts.init(document.querySelector("#resultPieChart"));
                                            
                                            const updateChart = (data) => {
                                                chartInstance.setOption({
                                                    tooltip: {
                                                        trigger: 'item',
                                                        formatter: '{a} <br/>{b}: {c} ({d}%)'
                                                    },
                                                    legend: {
                                                        orient: 'vertical',
                                                        left: 'left'
                                                    },
                                                    series: [{
                                                        name: 'User Count',
                                                        type: 'pie',
                                                        radius: '50%',
                                                        data: [
                                                            { value: data['0-50'], name: '0-50%' },
                                                            { value: data['51-70'], name: '51-70%' },
                                                            { value: data['71-90'], name: '71-90%' },
                                                            { value: data['91-100'], name: '91-100%' }
                                                        ],
                                                        emphasis: {
                                                            itemStyle: {
                                                                shadowBlur: 10,
                                                                shadowOffsetX: 0,
                                                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                                                            }
                                                        }
                                                    }]
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
                                        });
                                    </script>
                                </div>
                            </div>
                        </div><!-- End Result Pie Chart -->

                        <!-- Recent Businesses -->
                        <div class="col-12">
                            <div class="card recent-sales overflow-auto">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.recent_businesses') }} <span>| {{ __('messages.latest_additions') }}</span></h5>
                                    <table class="table table-borderless datatable">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ __('messages.id') }}</th>
                                                <th scope="col">{{ __('messages.name') }}</th>
                                                <th scope="col">{{ __('messages.email') }}</th>
                                                <th scope="col">{{ __('messages.business_code') }}</th>
                                                <th scope="col">{{ __('messages.users') }}</th>
                                                <th scope="col">{{ __('messages.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recent_businesses as $business)
                                                <tr>
                                                    <th scope="row"><a
                                                            href="{{ route('updatebusiness', $business->id) }}">#{{ $business->id }}</a>
                                                    </th>
                                                    <td>{{ $business->name }}</td>
                                                    <td>{{ $business->email }}</td>
                                                    <td>{{ $business->business_code ?? __('messages.na') }}</td>
                                                    <td>{{ $business->users_count }}</td>
                                                    <td>
                                                        @if ($business->status == 1)
                                                            <span class="badge bg-success">{{ __('messages.active') }}</span>
                                                        @else
                                                            <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">{{ __('messages.no_recent_businesses') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><!-- End Recent Businesses -->

                        <!-- Recent Contacts -->
                        <div class="col-12">
                            <div class="card top-selling overflow-auto">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('messages.recent_contact_inquiries') }} <span>| {{ __('messages.latest') }}</span></h5>
                                    <table class="table table-borderless datatable">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ __('messages.id') }}</th>
                                                <th scope="col">{{ __('messages.name') }}</th>
                                                <th scope="col">{{ __('messages.email') }}</th>
                                                <th scope="col">{{ __('messages.subject') }}</th>
                                                <th scope="col">{{ __('messages.date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recent_contacts as $contact)
                                                <tr>
                                                    <th scope="row"><a
                                                            href="{{ route('viewcontact', $contact->id) }}">#{{ $contact->id }}</a>
                                                    </th>
                                                    <td>{{ $contact->name }}</td>
                                                    <td>{{ $contact->email }}</td>
                                                    <td>{{ Str::limit($contact->subject, 30) }}</td>
                                                    <td>{{ $contact->created_at->format('M d, Y') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">{{ __('messages.no_recent_contacts') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><!-- End Recent Contacts -->

                        <!-- Top Selling -->
                        {{-- <div class="col-12">
							<div class="card top-selling overflow-auto">
								<div class="filter">
									<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
									<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
										<li class="dropdown-header text-start"><h6>Filter</h6> </li>
										<li><a class="dropdown-item" href="#">Today</a></li>
										<li><a class="dropdown-item" href="#">This Month</a></li>
										<li><a class="dropdown-item" href="#">This Year</a></li>
									</ul>
								</div>
								<div class="card-body pb-0">
									<h5 class="card-title">Top Selling <span>| Today</span></h5>
									<table class="table table-borderless">
										<thead>
											<tr>
												<th scope="col">Preview</th>
												<th scope="col">Product</th>
												<th scope="col">Price</th>
												<th scope="col">Sold</th>
												<th scope="col">Revenue</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<th scope="row"><a href="#"><img src="{{url('public/assets/img/product-1.jpg')}}" alt=""></a></th>
												<td><a href="#" class="text-primary fw-bold">Ut inventore ipsa voluptas nulla</a></td>
												<td>$64</td>
												<td class="fw-bold">124</td>
												<td>$5,828</td>
											</tr>
											<tr>
												<th scope="row"><a href="#"><img src="{{url('public/assets/img/product-2.jpg')}}" alt=""></a></th>
												<td><a href="#" class="text-primary fw-bold">Exercitationem similique doloremque</a></td>
												<td>$46</td>
												<td class="fw-bold">98</td>
												<td>$4,508</td>
											</tr>
											<tr>
												<th scope="row"><a href="#"><img src="{{url('public/assets/img/product-3.jpg')}}" alt=""></a></th>
												<td><a href="#" class="text-primary fw-bold">Doloribus nisi exercitationem</a></td>
												<td>$59</td>
												<td class="fw-bold">74</td>
												<td>$4,366</td>
											</tr>
											<tr>
												<th scope="row"><a href="#"><img src="{{url('public/assets/img/product-4.jpg')}}" alt=""></a></th>
												<td><a href="#" class="text-primary fw-bold">Officiis quaerat sint rerum error</a></td>
												<td>$32</td>
												<td class="fw-bold">63</td>
												<td>$2,016</td>
											</tr>
											<tr>
												<th scope="row"><a href="#"><img src="{{url('public/assets/img/product-5.jpg')}}" alt=""></a></th>
												<td><a href="#" class="text-primary fw-bold">Sit unde debitis delectus repellendus</a></td>
												<td>$79</td>
												<td class="fw-bold">41</td>
												<td>$3,239</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div><!-- End Top Selling --> --}}
                    </div>
                </div><!-- End Left side columns -->

                <!-- Right side columns -->
                {{-- <div class="col-lg-4">
				<!-- Recent Activity -->
					<div class="card">
						<div class="filter">
							<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
							<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
								<li class="dropdown-header text-start"><h6>Filter</h6></li>
								<li><a class="dropdown-item" href="#">Today</a></li>
								<li><a class="dropdown-item" href="#">This Month</a></li>
								<li><a class="dropdown-item" href="#">This Year</a></li>
							</ul>
						</div>
						<div class="card-body">
							<h5 class="card-title">Recent Activity <span>| Today</span></h5>
							<div class="activity">
								<div class="activity-item d-flex">
									<div class="activite-label">32 min</div>
									<i class='bi bi-circle-fill activity-badge text-success align-self-start'></i>
									<div class="activity-content">
										Quia quae rerum <a href="#" class="fw-bold text-dark">explicabo officiis</a> beatae
									</div>
								</div><!-- End activity item-->

								<div class="activity-item d-flex">
									<div class="activite-label">56 min</div>
									<i class='bi bi-circle-fill activity-badge text-danger align-self-start'></i>
									<div class="activity-content">
										Voluptatem blanditiis blanditiis eveniet
									</div>
								</div><!-- End activity item-->

								<div class="activity-item d-flex">
									<div class="activite-label">2 hrs</div>
									<i class='bi bi-circle-fill activity-badge text-primary align-self-start'></i>
									<div class="activity-content">
										Voluptates corrupti molestias voluptatem
									</div>
								</div><!-- End activity item-->

								<div class="activity-item d-flex">
									<div class="activite-label">1 day</div>
									<i class='bi bi-circle-fill activity-badge text-info align-self-start'></i>
									<div class="activity-content">
										Tempore autem saepe <a href="#" class="fw-bold text-dark">occaecati voluptatem</a> tempore
									</div>
								</div><!-- End activity item-->

								<div class="activity-item d-flex">
									<div class="activite-label">2 days</div>
									<i class='bi bi-circle-fill activity-badge text-warning align-self-start'></i>
									<div class="activity-content">
										Est sit eum reiciendis exercitationem
									</div>
								</div><!-- End activity item-->

								<div class="activity-item d-flex">
									<div class="activite-label">4 weeks</div>
									<i class='bi bi-circle-fill activity-badge text-muted align-self-start'></i>
									<div class="activity-content">
										Dicta dolorem harum nulla eius. Ut quidem quidem sit quas
									</div>
								</div><!-- End activity item-->
							</div>
						</div>
					</div><!-- End Recent Activity -->

					<!-- Budget Report -->
					<div class="card">
						<div class="filter">
							<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
							<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
								<li class="dropdown-header text-start"><h6>Filter</h6></li>
								<li><a class="dropdown-item" href="#">Today</a></li>
								<li><a class="dropdown-item" href="#">This Month</a></li>
								<li><a class="dropdown-item" href="#">This Year</a></li>
							</ul>
						</div>
						<div class="card-body pb-0">
							<h5 class="card-title">Budget Report <span>| This Month</span></h5>
							<div id="budgetChart" style="min-height: 400px;" class="echart"></div>
						</div>
					</div><!-- End Budget Report -->


					<!-- News & Updates Traffic -->

					<!-- News & Updates Traffic -->
					<div class="card">
						<div class="filter">
							<a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
							<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
								<li class="dropdown-header text-start"><h6>Filter</h6></li>
								<li><a class="dropdown-item" href="#">Today</a></li>
								<li><a class="dropdown-item" href="#">This Month</a></li>
								<li><a class="dropdown-item" href="#">This Year</a></li>
							</ul>
						</div>
						<div class="card-body pb-0">
							<h5 class="card-title">News &amp; Updates <span>| Today</span></h5>
							<div class="news">
								<div class="post-item clearfix">
									<img src="{{url('public/assets/img/news-1.jpg')}}" alt="">
									<h4><a href="#">Nihil blanditiis at in nihil autem</a></h4>
									<p>Sit recusandae non aspernatur laboriosam. Quia enim eligendi sed ut harum...</p>
								</div>
								<div class="post-item clearfix">
									<img src="{{url('public/assets/img/news-2.jpg')}}" alt="">
									<h4><a href="#">Quidem autem et impedit</a></h4>
									<p>Illo nemo neque maiores vitae officiis cum eum turos elan dries werona nande...</p>
								</div>
								<div class="post-item clearfix">
									<img src="{{url('public/assets/img/news-3.jpg')}}" alt="">
									<h4><a href="#">Id quia et et ut maxime similique occaecati ut</a></h4>
									<p>Fugiat voluptas vero eaque accusantium eos. Consequuntur sed ipsam et totam...</p>
								</div>
								<div class="post-item clearfix">
									<img src="{{url('public/assets/img/news-4.jpg')}}" alt="">
									<h4><a href="#">Laborum corporis quo dara net para</a></h4>
									<p>Qui enim quia optio. Eligendi aut asperiores enim repellendusvel rerum cuder...</p>
								</div>
								<div class="post-item clearfix">
									<img src="{{url('public/assets/img/news-5.jpg')}}" alt="">
									<h4><a href="#">Et dolores corrupti quae illo quod dolor</a></h4>
									<p>Odit ut eveniet modi reiciendis. Atque cupiditate libero beatae dignissimos eius...</p>
								</div>
							</div><!-- End sidebar recent posts-->
						</div>
					</div><!-- End News & Updates -->
				</div><!-- End Right side columns --> --}}
            </div>
        </section>
    </main><!-- End #main -->
    @include('partials.footer')
    <!-- page-body-wrapper ends -->
@endsection
