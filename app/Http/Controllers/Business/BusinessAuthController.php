<?php

namespace App\Http\Controllers\Business;

use App\Classes\Business\BusinessCls;
use App\Http\Controllers\Controller;
use App\Repositories\Admin\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\DailyMail;

class BusinessAuthController extends Controller
{
    protected $BusinessCls;
    protected $BusinessRep;

    public function __construct(BusinessCls $BusinessCls, BusinessRepository $BusinessRep)
    {
        $this->BusinessCls = $BusinessCls;
        $this->BusinessRep = $BusinessRep;
    }

    public function ShowPasswordSetupForm($token)
    {
        $business = $this->BusinessRep->GetBusinessByToken($token);

        if (!$business) {
            Session::flash('message', 'Invalid or expired password setup link');
            Session::flash('icon', 'error');
            return redirect()->route('business.login');
        }

        return view('business.setup-password', ['token' => $token, 'business' => $business]);
    }

    public function SetupPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $result = $this->BusinessCls->SetupPassword($request->token, $request->password);

        if ($result['success']) {
            Session::flash('message', 'Password set up successfully! You can now login.');
            Session::flash('icon', 'success');
            return redirect()->route('business.login');
        } else {
            Session::flash('message', $result['message']);
            Session::flash('icon', 'error');
            return back();
        }
    }

    public function ShowLoginForm()
    {
        if (Auth::guard('business')->check()) {
            return redirect()->route('business.dashboard');
        }
        return view('business.login');
    }

    public function ShowForgotForm()
    {
        return view('business.forgot-password');
    }

    public function SendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $business = $this->BusinessRep->GetBusinessByEmail($request->email);

        if (!$business) {
            Session::flash('message', 'No business found with that email');
            Session::flash('icon', 'error');
            return back()->withInput();
        }

        $token = $business->generatePasswordSetupToken();

        $data = [
            'business_name' => $business->name,
            'business_email' => $business->email,
            'setup_link' => route('business.password.setup', ['token' => $token]),
        ];

        Mail::to($business->email)->send(new DailyMail('Reset your password - ' . config('app.name'), 'emails.forgot_password', $data));

        Session::flash('message', 'Password reset link sent to your email');
        Session::flash('icon', 'success');
        return redirect()->route('business.login');
    }

    public function Login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->has('remember_me');
        $result = $this->BusinessCls->Login($request->email, $request->password, $remember);

        if ($result['success']) {
            return redirect()->route('business.dashboard');
        } else {
            Session::flash('message', $result['message']);
            Session::flash('icon', 'error');
            return back()->withInput();
        }
    }

    public function Logout()
    {
        Auth::guard('business')->logout();
        Session::flash('message', 'Logged out successfully');
        Session::flash('icon', 'success');
        return redirect()->route('business.login');
    }
}
