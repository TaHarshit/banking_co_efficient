<?php

namespace App\Http\Controllers\Business;

use App\Classes\Business\BusinessCls;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class BusinessDashboardController extends Controller
{
    protected $BusinessCls;

    public function __construct(BusinessCls $BusinessCls)
    {
        $this->BusinessCls = $BusinessCls;
    }

    public function Dashboard(Request $request)
    {
        $business = Auth::guard('business')->user();

        // Get employee statistics
        $totalEmployees = $business->employees()->count();
        $recentEmployees = $business->employees()->latest()->take(5)->get();

        // Get user statistics
        $totalUsers = $business->users()->count();
        $activeUsers = $business->users()->where('status', 1)->count();
        $pendingUsers = $business->users()->where('status', 2)->count();
        $recentUsers = $business->users()->latest()->take(5)->get();

        // Dummy seat values (to be configured after pricing model is decided)
        $totalSeats = 50;
        $seatsRemaining = $totalSeats - $totalUsers;

        // Get exam templates for this business
        $exam_templates = \App\Models\SkillAssessmentExamTemplate::where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        // Handle exam template filter
        $exam_template_id = $request->query('exam_template_id');
        $exam_stats = \App\Models\SkillAssessmentExam::getPercentageStats($business->id, $exam_template_id);

        return view('business.dashboard', [
            'business' => $business,
            'page_name' => 'Dashboard',
            'totalEmployees' => $totalEmployees,
            'recentEmployees' => $recentEmployees,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'pendingUsers' => $pendingUsers,
            'recentUsers' => $recentUsers,
            'totalSeats' => $totalSeats,
            'seatsRemaining' => $seatsRemaining,
            'exam_stats' => $exam_stats,
            'exam_templates' => $exam_templates,
            'selected_exam_template' => $exam_template_id
        ]);
    }

    public function Profile()
    {
        $business = Auth::guard('business')->user();
        return view('business.profile', ['business' => $business, 'page_name' => 'Profile']);
    }

    public function UpdateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'logo' => $request->hasFile('logo') ? 'image|mimes:jpg,jpeg,png|max:2048' : '',
            'address' => 'nullable|max:1000',
        ]);

        $business = Auth::guard('business')->user();
        $logo = $request->file('logo');

        $result = $this->BusinessCls->UpdateProfile(
            $business->id,
            $request->name,
            $logo,
            $request->address
        );

        if ($result['success']) {
            Session::flash('message', $result['message']);
            Session::flash('icon', 'success');
        } else {
            Session::flash('message', $result['message']);
            Session::flash('icon', 'error');
        }

        return redirect()->route('business.profile');
    }
}
