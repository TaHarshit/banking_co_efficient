<?php

namespace App\Http\Controllers\Admin;

use App\General\General;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Classes\Admin\UserCls;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Business;
use App\Models\ContactUs;
use App\Models\Question;
use App\Models\SkillAssessmentQuestion;
use App\Models\CaseStudyQuestion;

class UserController extends Controller
{
    protected $UserCls;

    public function __construct(UserCls $UserCls)
    {
        $this->UserCls = $UserCls;
    }

    public function index()
    {

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('index', ['page_name' => 'Login']);
    }

    public function dashboard(Request $request)
    {
        $user_count  = $this->UserCls->GetUsers()->count();
        $admin_count = $this->UserCls->GetAdmins()->count();

        $business_count = Business::count();
        $contact_count  = ContactUs::count();
        $personalized_question_count = Question::count();
        $exam_question_count = SkillAssessmentQuestion::count();
        $case_study_question_count = CaseStudyQuestion::count();

        $recent_businesses = Business::withCount('users')->orderBy('id', 'desc')->take(5)->get();
        $recent_contacts   = ContactUs::orderBy('id', 'desc')->take(5)->get();
        
        // Get exam templates and exam types for filter dropdowns
        $selected_exam_type = $request->query('exam_type');
        $exam_template_id = $request->query('exam_template_id');

        // Load all active templates (we'll filter client-side/server-side by selected type)
        $all_templates = \App\Models\SkillAssessmentExamTemplate::where('is_active', true)
            ->orderBy('title')
            ->get();

        // Build exam type options: 'global' (templates with NULL business_id) and 'business' (templates with a business_id)
        $has_global = \App\Models\SkillAssessmentExamTemplate::whereNull('business_id')->where('is_active', true)->exists();
        $has_business = \App\Models\SkillAssessmentExamTemplate::whereNotNull('business_id')->where('is_active', true)->exists();

        $exam_types = [];
        if ($has_global) {
            $exam_types['global'] = __('messages.global_exams') ?? 'Global Exams';
        }
        if ($has_business) {
            $exam_types['business'] = __('messages.business_exams') ?? 'Business Exams';
        }

        // Filter exam templates by selected exam type (if any). 'global' => business_id NULL, 'business' => has business_id
        $exam_templates = $all_templates->filter(function ($t) use ($selected_exam_type) {
            if (!$selected_exam_type) return true;
            if ($selected_exam_type === 'global') return !$t->business_id;
            return (bool) $t->business_id;
        })->pluck('title', 'id')->toArray();

        // Get exam stats applying both template and type filters
        $exam_stats = \App\Models\SkillAssessmentExam::getPercentageStats(null, $exam_template_id, $selected_exam_type);

        return view('dashboard', [
            'page_name'         => 'Dashboard',
            'user_count'        => $user_count,
            'admin_count'       => $admin_count,
            'business_count'    => $business_count,
            'contact_count'     => $contact_count,
            'personalized_question_count' => $personalized_question_count,
            'exam_question_count' => $exam_question_count,
            'case_study_question_count' => $case_study_question_count,
            'recent_businesses' => $recent_businesses,
            'recent_contacts'   => $recent_contacts,
            'exam_stats'        => $exam_stats,
            'exam_templates'    => $exam_templates,
            'exam_types'        => $exam_types,
            'selected_exam_template' => $exam_template_id,
            'selected_exam_type' => $selected_exam_type
        ]);
    }

    public function Signin(Request $request)
    {
        $validatedData = $request->validate([
            'email'     => 'required|email',
            'password'  => 'required'
        ]);
        return $this->UserCls->Signin($request);
    }

    public function logout(Request $request)
    {
        auth()->guard('web')->logout();
        return redirect()->route('login');
    }

    public function EditProfile(Request $request)
    {
        $data = $this->UserCls->GetUser(Auth::user()->id);
        return view('users.editprofile', ['data' => $data, 'page_name' => 'Settings']);
    }

    public function updateProfile(Request $request)
    {

        $validatedData  = $request->validate([
            'name'          => 'required|max:100',
            'surname'       => 'required|max:100',
            'username'      => 'nullable|max:100|unique:users,username' . ($request->id ? ",$request->id,id" : ''),
            'phone_no'      => 'required|max:20',
            'email'         => 'required|email|max:255|unique:users,email' . ($request->id ? ",$request->id,id" : ',NULL,id'),
            'password'      => $request->id <= 0 ? 'required|' : '|' . 'max:255',
            'job_title'     => 'nullable|max:255',
            'institution'   => 'nullable|max:255',
            'department'    => 'nullable|max:255',
            'year_of_experience' => 'nullable|max:255',
        ]);
        $image = $request->file('profile_image');
        return $this->UserCls->updateProfile($request, $image, $request->id);
    }

    public function ManageUsers(Request $request)
    {
        $filter = $request->query('company', '');
        $users = $this->UserCls->GetUsers($filter);
        $businesses = Business::where('status', 1)->get();
        return view('users.manage', [
            'users' => $users,
            'page_name' => 'Users',
            'businesses' => $businesses,
            'current_filter' => $filter
        ]);
    }

    public function ExportUsers(Request $request)
    {
        $filter = $request->query('company', '');
        return $this->UserCls->ExportUsers($filter);
    }

    public function CreateUser()
    {
        $businesses = Business::where('status', 1)->get();
        return view('users.addedit', compact('businesses'));
    }

    public function UpdateUser($id)
    {
        $data = $this->UserCls->GetUser($id);
        $businesses = Business::where('status', 1)->get();
        return view('users.addedit', compact('data', 'businesses'));
    }

    public function DeleteUser($id)
    {
        return $this->UserCls->DeleteUser($id);
    }

    public function StoreUser(Request $request)
    {
        $validatedData = $request->validate([
            'name'          => 'required|max:100',
            'surname'       => 'required|max:100',
            'username'      => 'nullable|max:100|unique:users,username' . ($request->id ? ",$request->id,id" : ''),
            'phone_no'      => 'required|max:20',
            'email'         => 'required|email|max:255|unique:users,email' . ($request->id ? ",$request->id,id" : ',NULL,id'),
            'password'      => $request->id > 0 ? 'nullable|max:255' : 'nullable', // Password will be auto-generated for new users
            'job_title'     => 'nullable|max:255',
            'institution'   => 'nullable|max:255',
            'department'    => 'nullable|max:255',
            'year_of_experience' => 'nullable|max:255',
            'business_id'   => 'nullable|exists:businesses,id',
            'status'        => 'required|in:0,1'
        ]);

        $password = $request->password;
        if ($request->id <= 0) {
            $password = \Illuminate\Support\Str::random(10);
        }

        return $this->UserCls->StoreUser(
            $request->name, 
            $request->surname, 
            $request->email, 
            $password, 
            $request->phone_no, 
            $request->status, 
            $request->id,
            $request->username,
            $request->job_title,
            $request->institution,
            $request->department,
            $request->year_of_experience,
            $request->business_id
        );
    }

    public function ChangeStatus(Request $request)
    {
        return $this->UserCls->ChangeStatus($request->id, $request->status);
    }

    public function Error404()
    {
        return view('error.404');
    }
}
