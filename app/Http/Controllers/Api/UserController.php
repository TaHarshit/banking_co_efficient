<?php

namespace App\Http\Controllers\Api;

use App\Classes\Api\EmailSubscribeCls;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Api\UserCls;
use App\General\General;
use App\Models\User;
use Carbon\Carbon;
use Auth;
use DB;

class UserController extends Controller
{
    protected $UserCls;

    public function __construct(UserCls $UserCls)
    {
        $this->UserCls = $UserCls;
    }

    public function SignUp(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->SignUp($postData, $request);
        return get_response($request, $data);
    }

    public function Login(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->Login($postData);
        return get_response($request, $data);
    }

    public function GetProfile(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->GetProfile($postData);
        return get_response($request, $data);
    }

    public function ForgotPassword(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->ForgotPassword($postData);
        return get_response($request, $data);
    }

    public function ChangePassword(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->ChangePassword($postData);
        return get_response($request, $data);
    }

    public function GetSetting(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->GetSetting($postData);
        return get_response($request, $data);
    }

    public function DeleteAccount(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->DeleteAccount($postData);
        return get_response($request, $data);
    }

    public function Logout(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->Logout($postData);
        return get_response($request, $data);
    }

    public function CompleteProfile(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->CompleteProfile($postData, $request);
        return get_response($request, $data);
    }

    public function UpdateProfileImages(Request $request)
    {
        $postData     = General::stripRequest($request->all());
        $data         = $this->UserCls->UpdateProfileImages($postData, $request);
        return get_response($request, $data);
    }

    public function ResetPassword($token)
    {
        return view('users.resetpassword', ['token' => $token]);
    }

    public function UpdatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return view('users.resetpassword', [
                'token' => $request->token,
                'message' => $validator->errors()->first(),
                'icon' => 'danger'
            ]);
        }

        $response = $this->UserCls->ResetPassword($request->all());
        
        if ($response['code'] == 200) {
            return view('users.resetpassword', [
                'token' => $request->token,
                'message' => 'Password reset successfully. You can now log in to the app.',
                'icon' => 'success'
            ]);
        } else {
            return view('users.resetpassword', [
                'token' => $request->token,
                'message' => $response['message'],
                'icon' => 'danger'
            ]);
        }
    }
}
