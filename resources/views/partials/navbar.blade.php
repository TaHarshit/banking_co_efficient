<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="logo d-flex align-items-center">
            <img src="{{ url('public/assets/img/logo.png') }}" alt="logo">
            {{-- <span class="d-none d-lg-block">Your Logo</span> --}}
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->
    <!--<div class="search-bar">
  <form class="search-form d-flex align-items-center" method="POST" action="#">
   <input type="text" name="query" placeholder="Search" title="Enter search keyword">
   <button type="submit" title="Search"><i class="bi bi-search"></i></button>
  </form>
 </div>-->

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <!--<li class="nav-item d-block d-lg-none">
    <a class="nav-link nav-icon search-bar-toggle " href="#">
     <i class="bi bi-search"></i>
    </a>
   </li>

   <li class="nav-item dropdown">
    <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
     <i class="bi bi-bell"></i>
     <span class="badge bg-primary badge-number">4</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
     <li class="dropdown-header">
      You have 4 new notifications
      <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
     </li>
     <li> <hr class="dropdown-divider"> </li>
     <li class="notification-item">
      <i class="bi bi-exclamation-circle text-warning"></i>
      <div>
       <h4>Lorem Ipsum</h4>
       <p>Quae dolorem earum veritatis oditseno</p>
       <p>30 min. ago</p>
      </div>
     </li>
     <li> <hr class="dropdown-divider"> </li>
     <li class="notification-item">
      <i class="bi bi-x-circle text-danger"></i>
      <div>
       <h4>Atque rerum nesciunt</h4>
       <p>Quae dolorem earum veritatis oditseno</p>
       <p>1 hr. ago</p>
      </div>
     </li>
     <li> <hr class="dropdown-divider"> </li>
     <li class="notification-item">
      <i class="bi bi-check-circle text-success"></i>
      <div>
       <h4>Sit rerum fuga</h4>
       <p>Quae dolorem earum veritatis oditseno</p>
       <p>2 hrs. ago</p>
      </div>
     </li>
     <li>
      <hr class="dropdown-divider">
     </li>
     <li class="notification-item">
      <i class="bi bi-info-circle text-primary"></i>
      <div>
       <h4>Dicta reprehenderit</h4>
       <p>Quae dolorem earum veritatis oditseno</p>
       <p>4 hrs. ago</p>
      </div>
     </li>
     <li> <hr class="dropdown-divider"> </li>
     <li class="dropdown-footer">
      <a href="#">Show all notifications</a>
     </li>
    </ul>
   </li>

   <li class="nav-item dropdown">
    <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
     <i class="bi bi-chat-left-text"></i>
     <span class="badge bg-success badge-number">3</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
     <li class="dropdown-header">
      You have 3 new messages
      <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
     </li>
     <li>
      <hr class="dropdown-divider">
     </li>
     <li class="message-item">
      <a href="#">
       <img src="{{ url('public/assets/img/messages-1.jpg') }}" alt="" class="rounded-circle">
       <div>
        <h4>Maria Hudson</h4>
        <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
        <p>4 hrs. ago</p>
       </div>
      </a>
     </li>
     <li>
      <hr class="dropdown-divider">
     </li>
     <li class="message-item">
      <a href="#">
       <img src="{{ url('public/assets/img/messages-2.jpg') }}" alt="" class="rounded-circle">
       <div>
        <h4>Anna Nelson</h4>
        <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
        <p>6 hrs. ago</p>
       </div>
      </a>
     </li>
     <li>
      <hr class="dropdown-divider">
     </li>
     <li class="message-item">
      <a href="#">
       <img src="{{ url('public/assets/img/messages-3.jpg') }}" alt="" class="rounded-circle">
       <div>
        <h4>David Muldon</h4>
        <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
        <p>8 hrs. ago</p>
       </div>
      </a>
     </li>
     <li>
      <hr class="dropdown-divider">
     </li>
     <li class="dropdown-footer">
      <a href="#">Show all messages</a>
     </li>
    </ul>
   </li>-->

            <!-- Language Switcher -->
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <img src="{{ url('public/assets/img/flags/' . app()->getLocale() . '.svg') }}"
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
                            href="{{ route('locale.switch', 'en') }}">
                            <img src="{{ url('public/assets/img/flags/en.svg') }}" alt="English"
                                style="width: 20px; height: 14px; margin-right: 8px; border: 1px solid #ddd;">
                            <span>{{ __('messages.english') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() == 'fr' ? 'active' : '' }}"
                            href="{{ route('locale.switch', 'fr') }}">
                            <img src="{{ url('public/assets/img/flags/fr.svg') }}" alt="French"
                                style="width: 20px; height: 14px; margin-right: 8px; border: 1px solid #ddd;">
                            <span>{{ __('messages.french') }}</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Language Switcher -->

            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                    data-bs-toggle="dropdown">
                    {{-- <img src="{{asset('storage/app/public/profile_image/'.Auth::user()->profile_image)}}" alt="Profile" class="rounded-circle"> --}}
                    <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name }}</span>
                </a><!-- End Profile Iamge Icon -->
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6>{{ Auth::user()->name }}</h6>
                        <span>{{ __('messages.admin_role') }}</span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('setting') }}">
                            <i class="bi bi-person"></i>
                            <span>{{ __('messages.my_profile') }}</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <!--<li>
      <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
       <i class="bi bi-gear"></i>
       <span>Account Settings</span>
      </a>
     </li>
     <li> <hr class="dropdown-divider"> </li>
     <li>
      <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
       <i class="bi bi-question-circle"></i>
       <span>Need Help?</span>
      </a>
     </li>
     <li> <hr class="dropdown-divider"></li>-->
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('messages.sign_out') }}</span>
                        </a>
                    </li>
                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->
        </ul>
    </nav><!-- End Icons Navigation -->
</header><!-- End Header -->
