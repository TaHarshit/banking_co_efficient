<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : 'collapsed' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid"></i>
                <span>{{ __('messages.dashboard') }}</span>
            </a>
        </li><!-- End Dashboard Nav -->
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('manageusers', 'createuser', 'updateuser', 'deleteuser', 'exportusers') ? 'active' : 'collapsed' }}" href="{{ route('manageusers') }}">
                <i class="bi bi-person"></i>
                <span>{{ __('messages.users') }}</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('managebusinesses', 'createbusiness', 'updatebusiness', 'deletebusiness', 'resendbusinessinvitation') ? 'active' : 'collapsed' }}" href="{{ route('managebusinesses') }}">
                <i class="bi bi-building"></i>
                <span>{{ __('messages.businesses') }}</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('managesections', 'createsection', 'updatesection', 'deletesection', 'managequestions', 'createquestion', 'updatequestion', 'deletequestion', 'exportquestions', 'importquestions', 'downloadexample', 'deleteallquestions') ? 'active' : 'collapsed' }}" href="{{ route('managesections') }}">
                <i class="bi bi-diagram-3"></i>
                <span>{{ __('messages.personalized_experience') }}</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.case_study_questions.*') ? 'active' : 'collapsed' }}" href="{{ route('admin.case_study_questions.index') }}">
                <i class="bi bi-journal-text"></i>
                <span>{{ __('messages.case_study_questions') }}</span>
            </a>
        </li>

        <li class="nav-item">
            @php
                $isSkillAssessmentActive = request()->routeIs('manageskillassessmentexamtemplates', 'createskillassessmentexamtemplate', 'updateskillassessmentexamtemplate', 'deleteskillassessmentexamtemplate', 'manageskillassessmentexams', 'viewskillassessmentexam', 'manageskillassessmentsections', 'createskillassessmentsection', 'updateskillassessmentsection', 'deleteskillassessmentsection', 'manageskillassessmentquestions', 'createskillassessmentquestion', 'updateskillassessmentquestion', 'deleteskillassessmentquestion');
            @endphp
            <a class="nav-link {{ $isSkillAssessmentActive ? 'active' : 'collapsed' }}" data-bs-target="#skill-assessment-nav" data-bs-toggle="collapse"
                href="#">
                <i class="bi bi-clipboard-check"></i>
                <span>{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="skill-assessment-nav" class="nav-content collapse {{ $isSkillAssessmentActive ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('manageskillassessmentexamtemplates') }}" class="{{ request()->routeIs('manageskillassessmentexamtemplates', 'createskillassessmentexamtemplate', 'updateskillassessmentexamtemplate') ? 'active' : '' }}">
                        <i class="bi bi-circle"></i><span>{{ __('messages.exams') ?? 'Exams' }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('manageskillassessmentexams') }}" class="{{ request()->routeIs('manageskillassessmentexams', 'viewskillassessmentexam') ? 'active' : '' }}">
                        <i class="bi bi-circle"></i><span>{{ __('messages.exam_results') ?? 'Exam Results' }}</span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('managecontacts', 'viewcontact') ? 'active' : 'collapsed' }}" href="{{ route('managecontacts') }}">
                <i class="bi bi-envelope"></i>
                <span>{{ __('messages.contact_us') }}</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.activity_logs.index') ? 'active' : 'collapsed' }}" href="{{ route('admin.activity_logs.index') }}">
                <i class="bi bi-list-check"></i>
                <span>Activity Logs</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('addeditsetting') ? 'active' : 'collapsed' }}" href="{{ route('addeditsetting', ['id' => 1]) }}">
                <i class="bi bi-gear"></i>
                <span>{{ __('messages.settings') }}</span>
            </a>
        </li>

        {{-- <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.pdf.manage') ? 'active' : 'collapsed' }}" href="{{ route('admin.pdf.manage') }}">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>{{ __('messages.manage_pdf') }}</span>
            </a>
        </li> --}}
        <!-- End Profile Page Nav -->
    </ul>
</aside><!-- End Sidebar-->
