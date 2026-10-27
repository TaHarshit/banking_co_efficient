<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('title', __('messages.dashboard')) - {{ config('app.name') }}</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="{{ url('assets/img/favicon.png') }}" rel="icon">
    <link href="{{ url('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ url('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ url('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ url('assets/css/style.css') }}" rel="stylesheet">

    <style>
        :root {
            --primary: {{ env('APP_THEME_COLOR', '#4154f1') }};
            --hov-primary: {{ env('APP_HOVER_THEME_COLOR', '#012970') }};
        }
    </style>

    @yield('pagewisestyle')
</head>

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('business.dashboard') }}" class="logo d-flex align-items-center">
                <img src="{{ url('assets/img/logo.png') }}" alt="">
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                @if (session()->has('admin_impersonate_id'))
                    <li class="nav-item pe-3">
                        <a href="{{ route('business.leave_impersonate') }}"
                            class="btn btn-sm btn-danger d-flex align-items-center gap-1 shadow-sm fw-semibold"
                            title="{{ __('messages.back_to_super_admin') }}">
                            <i class="bi bi-box-arrow-left"></i>
                            <span class="d-none d-sm-inline">{{ __('messages.back_to_super_admin') }}</span>
                        </a>
                    </li>
                @endif
                <!-- Language Switcher -->
                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <img src="{{ url('assets/img/flags/' . app()->getLocale() . '.svg') }}"
                            alt="{{ app()->getLocale() }}" style="width: 24px; height: 16px; border: 1px solid #ddd;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <li class="dropdown-header">
                            <h6>{{ __('messages.language') }}</h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() == 'en' ? 'active' : '' }}"
                                href="{{ route('business.locale.switch', 'en') }}">
                                <img src="{{ url('assets/img/flags/en.svg') }}" alt="English"
                                    style="width: 20px; height: 14px; margin-right: 8px; border: 1px solid #ddd;">
                                <span>{{ __('messages.english') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() == 'fr' ? 'active' : '' }}"
                                href="{{ route('business.locale.switch', 'fr') }}">
                                <img src="{{ url('assets/img/flags/fr.svg') }}" alt="French"
                                    style="width: 20px; height: 14px; margin-right: 8px; border: 1px solid #ddd;">
                                <span>{{ __('messages.french') }}</span>
                            </a>
                        </li>
                    </ul>
                </li><!-- End Language Switcher -->

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        @if (auth()->guard('business')->user()->logo &&
                                Storage::exists('public/business_logos/' . auth()->guard('business')->user()->logo))
                            <img src="{{ asset('storage/business_logos/' . auth()->guard('business')->user()->logo) }}"
                                alt="Profile" class="rounded-circle">
                        @else
                            <div
                                style="width: 36px; height: 36px; background: #4154f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                {{ substr(auth()->guard('business')->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <span class="d-none d-md-block dropdown-toggle ps-2">
                            @if (session('business_role') === 'manager')
                                {{ session('manager_name') }}
                                <span class="badge bg-info text-dark ms-1" style="font-size: 11px;">{{ __('messages.bank_manager') ?? 'Manager' }}</span>
                            @else
                                {{ auth()->guard('business')->user()->name }}
                            @endif
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            @if (session('business_role') === 'manager')
                                <h6>{{ session('manager_name') }}</h6>
                                <span>{{ session('manager_email') }}</span>
                                @if (session('manager_department'))
                                    <small class="text-primary d-block fw-semibold"><i class="bi bi-building me-1"></i>{{ session('manager_department') }}</small>
                                @endif
                                <small class="text-muted d-block">{{ auth()->guard('business')->user()->name }}</small>
                            @else
                                <h6>{{ auth()->guard('business')->user()->name }}</h6>
                                <span>{{ auth()->guard('business')->user()->email }}</span>
                            @endif
                        </li>
                        @if (session('business_role') !== 'manager')
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('business.profile') }}">
                                    <i class="bi bi-person"></i>
                                    <span>{{ __('messages.my_profile') }}</span>
                                </a>
                            </li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @if (session()->has('admin_impersonate_id'))
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger fw-semibold" href="{{ route('business.leave_impersonate') }}">
                                    <i class="bi bi-box-arrow-left"></i>
                                    <span>{{ __('messages.back_to_super_admin') }}</span>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        @endif
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('business.logout') }}">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>{{ __('messages.sign_out') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('business.dashboard') ? '' : 'collapsed' }}"
                    href="{{ route('business.dashboard') }}">
                    <i class="bi bi-grid"></i>
                    <span>{{ __('messages.dashboard') }}</span>
                </a>
            </li>

            @if (session('business_role') === 'manager')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.skill-assessment.exam-results*') ? '' : 'collapsed' }}"
                        href="{{ route('business.skill-assessment.exam-results') }}">
                        <i class="bi bi-clipboard-check"></i>
                        <span>{{ __('messages.exam_results') ?? 'Exam Results' }}</span>
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.contacts*') ? '' : 'collapsed' }}"
                        href="{{ route('business.contacts') }}">
                        <i class="bi bi-envelope"></i>
                        <span>{{ __('messages.contact_us') }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.profile') ? '' : 'collapsed' }}"
                        href="{{ route('business.profile') }}">
                        <i class="bi bi-person"></i>
                        <span>{{ __('messages.my_profile') }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.managers*') ? '' : 'collapsed' }}"
                        href="{{ route('business.managers') }}">
                        <i class="bi bi-person-badge"></i>
                        <span>{{ __('messages.bank_managers') ?? 'Managers' }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.employees*') ? '' : 'collapsed' }}"
                        href="{{ route('business.employees') }}">
                        <i class="bi bi-people"></i>
                        <span>{{ __('messages.employees') }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.users*') ? '' : 'collapsed' }}"
                        href="{{ route('business.users') }}">
                        <i class="bi bi-person-check"></i>
                        <span>{{ __('messages.users') }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.sections*') || request()->routeIs('business.questions*') ? '' : 'collapsed' }}"
                        href="{{ route('business.sections') }}">
                        <i class="bi bi-diagram-3"></i>
                        <span>{{ __('messages.personalized_experience') ?? 'Personalized Experience' }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.policies*') ? '' : 'collapsed' }}"
                        href="{{ route('business.policies.index') }}">
                        <i class="bi bi-shield-check"></i>
                        <span>{{ __('messages.business_policies') }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.skill-assessment*') ? '' : 'collapsed' }}"
                        data-bs-target="#skill-assessment-nav" data-bs-toggle="collapse" href="#">
                        <i class="bi bi-clipboard-check"></i>
                        <span>{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="skill-assessment-nav"
                        class="nav-content collapse {{ request()->routeIs('business.skill-assessment*') ? 'show' : '' }}"
                        data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('business.skill-assessment.exams') }}"
                                class="{{ (request()->routeIs('business.skill-assessment.exams') || request()->routeIs('business.skill-assessment.exams.*') || request()->routeIs('business.skill-assessment.sections') || request()->routeIs('business.skill-assessment.sections.*') || request()->routeIs('business.skill-assessment.questions') || request()->routeIs('business.skill-assessment.questions.*')) && !request()->routeIs('business.skill-assessment.exam-results*') ? 'active' : '' }}">
                                <i class="bi bi-circle"></i><span>{{ __('messages.exams') ?? 'Exams' }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('business.skill-assessment.exam-results') }}"
                                class="{{ request()->routeIs('business.skill-assessment.exam-results*') ? 'active' : '' }}">
                                <i
                                    class="bi bi-circle"></i><span>{{ __('messages.exam_results') ?? 'Exam Results' }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.activity_logs*') ? '' : 'collapsed' }}"
                        href="{{ route('business.activity_logs.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>{{ __('messages.activity_logs') ?? 'Activity Logs' }}</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('business.privacy_policy*') ? '' : 'collapsed' }}"
                        href="{{ route('business.privacy_policy') }}">
                        <i class="bi bi-shield-lock"></i>
                        <span>{{ __('messages.privacy_policy_nav') ?? 'Privacy Policy' }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </aside>

    <main id="main" class="main">
        @if (session('business_role') === 'manager')
            <div class="alert alert-info border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between mb-4 py-2 px-3 rounded-3"
                style="background: #e0f2fe; color: #0369a1;">
                <div class="d-flex align-items-center gap-2 mb-1 mb-md-0">
                    <span class="badge bg-info text-dark px-2 py-1"><i class="bi bi-person-badge me-1"></i>{{ __('messages.bank_manager_mode') ?? 'Manager Mode' }}</span>
                    <span class="fw-medium">
                        {{ session('manager_name') }} &bull; {{ auth()->guard('business')->user()->name }}
                        @if (session('manager_department'))
                            <span class="badge bg-light text-dark border ms-2"><i class="bi bi-building me-1"></i>{{ session('manager_department') }}</span>
                        @endif
                    </span>
                </div>
            </div>
        @endif
        @if (session()->has('admin_impersonate_id'))
            <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between mb-4 py-2 px-3 rounded-3"
                style="background: linear-gradient(90deg, #fff3cd 0%, #ffeaa7 100%); color: #856404;">
                <div class="d-flex align-items-center gap-2 mb-1 mb-md-0">
                    <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-shield-lock-fill me-1"></i>{{ __('messages.super_admin_mode') }}</span>
                    <span class="fw-semibold">
                        {{ __('messages.logged_in_as_bank', ['name' => auth()->guard('business')->user()->name]) }}
                    </span>
                </div>
                <a href="{{ route('business.leave_impersonate') }}" class="btn btn-danger btn-sm d-flex align-items-center gap-1 fw-bold shadow-sm">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>{{ __('messages.back_to_super_admin') }}</span>
                </a>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; Copyright <strong><span>{{ config('app.name') }}</span></strong>.
            {{ __('messages.all_rights_reserved') }}
        </div>
    </footer>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="{{ url('assets/vendor/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ url('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ url('assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ url('assets/vendor/quill/quill.js') }}"></script>
    <script src="{{ url('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script src="{{ url('assets/vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ url('assets/vendor/php-email-form/validate.js') }}"></script>
    @yield('pagewisescript')

    <!-- Template Main JS File -->
    <script src="{{ url('assets/js/main.js') }}"></script>

    @yield('customjs')
</body>

</html>
