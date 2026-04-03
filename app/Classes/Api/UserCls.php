<?php

namespace App\Classes\Api;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Api\UserRepository;
use App\Repositories\Api\SettingsRepository;
use Illuminate\Support\Facades\Storage;
use App\General\Validate;
use App\General\General;
use App\Repositories\Api\ProfileImageRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserCls
{

    protected $UserRep;
    protected $ProfileImgRep;
    protected $SettingRep;

    public function __construct(UserRepository $UserRep, SettingsRepository $SettingRep)
    {
        $this->UserRep = $UserRep;
        $this->SettingRep = $SettingRep;
    }

    public function SignUp($postData, $request)
    {

        try {
            // Simplified signup - require only essential fields including unique username
            $requiredValidate = Validate::required($postData, array('name', 'username', 'email', 'password', 'device_token', 'platform'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            $emailValidate = Validate::email($postData, array('email'));
            if ($emailValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $emailValidate->errors()->first());
            }

            // Validate unique email
            $uniqueEmailValidate = Validate::unique($postData, array('email'));
            if ($uniqueEmailValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $uniqueEmailValidate->errors()->first());
            }

            // Validate unique username
            $uniqueUsernameValidate = Validate::uniqueUsername($postData, array('username'));
            if ($uniqueUsernameValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $uniqueUsernameValidate->errors()->first());
            }

            // Business association logic
            $businessId = null;
            $userStatus = 'active'; // Default status: 'active'=active, 'pending'=pending
            $isEmployee = false;

            if (!empty($postData['business_code'])) {
                // Validate business code
                $business = \App\Models\Business::where('business_code', $postData['business_code'])
                    ->where('status', 1)
                    ->first();

                if (!$business) {
                    return General::setResponse('VALIDATION_ERROR', 'Invalid business code. Please check and try again.');
                }

                $businessId = $business->id;

                // Check if email is in employees list
                $isEmployee = \App\Models\Employee::where('business_id', $business->id)
                    ->where('email', strtolower($postData['email']))
                    ->exists();

                // Set status based on employee check: 'active'=active, 'pending'=pending
                $userStatus = $isEmployee ? 'active' : 'pending';
            }

            DB::beginTransaction();

            // Add business_id and status to postData
            $postData['business_id'] = $businessId;
            $postData['status'] = $userStatus;

            $response = $this->UserRep->SignUp($postData);

            if ($response) {
                DB::commit();

                // If user is pending (status=2), return success without login
                if ($userStatus === 2) {
                    $data = General::setResponse('SUCCESS', "Registration submitted! Please wait for approval from the business.");
                    $data['data'] = [
                        'status' => 'pending',
                        'message' => 'Your account is pending approval from the business administrator.'
                    ];
                    return $data;
                }

                if (Auth::attempt(['email' => $postData['email'], 'password' => $postData['password']], false)) {
                    if (Auth::user()->status == 'active') {

                        DB::beginTransaction();
                        $user = $this->UserRep->GetUser(Auth::user()->id);
                        if ($postData['device_token'] != "") {
                            $this->UserRep->UpdateDeviceToken($postData['device_token'], $postData['platform'], $user->id);
                        }

                        $token                      = $user->createToken(env('API_KEY', ''))->accessToken;
                        $user['api_token']          = $token->token;
                        $user['device_token']       = $postData['device_token'];
                        $user['platform']           = $postData['platform'];

                        $this->UserRep->UpdateApiToken($token->token, $user->id);

                        DB::commit();

                        $data = General::setResponse('SUCCESS', "You have successfully signed up!");
                        $data['data'] = $user;
                        return $data;
                    } else {
                        return General::setResponse('VALIDATION_ERROR', 'Your account is inactive please contact to admin.');
                    }
                } else {
                    return General::setResponse('VALIDATION_ERROR', 'Email or password is incorrect.');
                }
            } else {
                return General::setResponse('VALIDATION_ERROR', 'Something went wrong!');
            }
        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function Login($postData)
    {
        try {
            $requiredValidate = Validate::required($postData, array('email', 'password'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            if (Auth::attempt(['email' => $postData['email'], 'password' => $postData['password']], false)) {
                $userStatus = Auth::user()->status;

                // Check for active status ('active' = active)
                if ($userStatus == 'active') {

                    DB::beginTransaction();
                    $user = $this->UserRep->GetUser(Auth::user()->id);

                    if ($postData['device_token'] != "") {
                        $this->UserRep->UpdateDeviceToken($postData['device_token'], $postData['platform'], $user->id);
                    }
                    // $user->tokens()->delete();

                    $token                      = $user->createToken(env('API_KEY', ''))->accessToken;

                    $user['api_token']          = $token->token;
                    $user['device_token']       = $postData['device_token'];
                    $user['platform']           = $postData['platform'];

                    unset($user['profile_images']);


                    $this->UserRep->UpdateApiToken($token->token, $user->id);


                    DB::commit();

                    $data = General::setResponse('SUCCESS', "You have successfully login!");
                    $data['data'] = $user;
                    return $data;
                } elseif ($userStatus == 'pending') {
                    Auth::logout();
                    return General::setResponse('VALIDATION_ERROR', 'Your account is pending approval from the business administrator.');
                } elseif ($userStatus == 'rejected') {
                    Auth::logout();
                    return General::setResponse('VALIDATION_ERROR', 'Your account has been rejected. Please contact the business administrator.');
                } else {
                    Auth::logout();
                    return General::setResponse('VALIDATION_ERROR', 'Your account is inactive please contact to admin.');
                }
            } else {
                return General::setResponse('VALIDATION_ERROR', 'Email or password is incorrect.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function SocialLogin($postData)
    {
        try {
            $requiredValidate = Validate::required($postData, array('email'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            DB::beginTransaction();
            $user = $this->UserRep->GetUserBySocialId($postData['apple_id'], $postData['google_id'], $postData['email']);


            if (!empty($user)) {
                if ($user->status == 'active') {

                    // if($postData['user_type']!=$user->user_type){

                    //     return General::setResponse('VALIDATION_ERROR', 'Email is registered for the role other than your selected role.');
                    // }

                    $token                  = $user->createToken(env('API_KEY', ''))->accessToken;
                    //  $profile_image          = General::checkImage($user->profile_image, 'user');
                    $user['api_token']      = $token->token;
                    //   $user['profile_image']  = $profile_image;

                    $this->UserRep->UpdateApiToken($token->token, $user->id);
                    if ($postData['apple_id'] != "") {
                        $this->UserRep->UpdateSocialId('apple_id', $postData['apple_id'], $user->id);
                        $user['apple_id'] = $postData['apple_id'];
                    }
                    if ($postData['google_id'] != "") {
                        $this->UserRep->UpdateSocialId('google_id', $postData['google_id'], $user->id);
                        $user['google_id'] = $postData['google_id'];
                    }

                    // if($postData['device_token']!=""){
                    //     $this->UserRep->UpdateDeviceToken($postData['device_token'], $postData['platform'], $user->id);
                    // }

                    $result = $user;
                } else {
                    return General::setResponse('VALIDATION_ERROR', 'Your account is inactive please contact to admin.');
                }
            } else {
                $result = array("id" => "", "user_type" => "", "name" => "", "surname" => "", "email" => "", "phone_no" => "", "address" => "", "description" => "", "qualifications" => "", "profile_image" => "", "category_id" => "", "sub_category_id" => "", "apple_id" => "", "device_token" => "", "email_verified_at" => "", "status" => "", "created_at" => "", "updated_at" => "", "deleted_at" => "", "token" => "");
            }
            DB::commit();

            $data = General::setResponse('SUCCESS', "You have successfully login!");
            $data['data'] = $result;
            return $data;
        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetProfile($postData)
    {
        try {
            $requiredValidate = Validate::required($postData, array('user_id'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            $user = $this->UserRep->GetUser($postData['user_id']);

            if (!empty($user)) {

                $data = General::setResponse('SUCCESS', "Profile get successfully.");
                $data['data'] = $user;
                return $data;
            } else {
                return General::setResponse('VALIDATION_ERROR', 'User not found!');
            }
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function ForgotPassword($postData)
    {

        try {

            $requiredValidate = Validate::required($postData, array('email'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            $emailValidate = Validate::email($postData, array('email'));
            if ($emailValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $emailValidate->errors()->first());
            }

            $user = $this->UserRep->GetUserByEmail($postData['email']);
            if (!empty($user)) {

                $status = Password::sendResetLink(array("email" => $postData['email']));

                if ($status === Password::RESET_LINK_SENT) {
                    $data = General::setResponse('SUCCESS', "We have emailed your password reset link!");
                    $data['data'] = array();
                    return $data;
                } else {
                    return General::setResponse('VALIDATION_ERROR', 'Please wait before retrying.');
                }
            } else {
                return General::setResponse('VALIDATION_ERROR', 'User not found with this email address!');
            }
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function ChangePassword($postData)
    {

        try {

            $requiredValidate = Validate::required($postData, array('new_password'));
            if ($requiredValidate->fails()) {
                return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first());
            }

            // if(!Hash::check($postData['old_password'], Auth::user()->password)){
            //     return General::setResponse('VALIDATION_ERROR', "Old Password Doesn't match!");
            // }

            $res    = $this->UserRep->ChangePassword($postData['new_password']);
            $data   = General::setResponse('SUCCESS', "Password changed successfully.");
            $data['data'] = array();
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetSetting()
    {
        try {
            $response = $this->SettingRep->GetSettings(1);

            $data   = General::setResponse('SUCCESS', "Setting Get successfully.");
            $data['data'] = $response;
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function CompleteProfile($postData, $request)
    {
        try {

            if (!empty($postData['dob'])) {
                $datedValidate = Validate::dateformat($postData, array('dob'), "Y-m-d");
                if ($datedValidate->fails()) {
                    return General::setResponse('VALIDATION_ERROR', $datedValidate->errors()->first());
                }
            }

            $UserObj = $this->UserRep->GetUser(Auth::user()->id);

            $update_profile = [
                'name'                   => isset($postData['name']) ? $postData['name'] : $UserObj->name,
                'country_code'           => isset($postData['country_code']) ? $postData['country_code'] : $UserObj->country_code,
                'phone_no'               => isset($postData['phone_no']) ? $postData['phone_no'] : $UserObj->phone_no,
                'job_title'              => isset($postData['job_title']) ? $postData['job_title'] : $UserObj->job_title,
                'institution'            => isset($postData['institution']) ? $postData['institution'] : $UserObj->institution,
                'department'             => isset($postData['department']) ? $postData['department'] : $UserObj->department,
                'year_of_experience'     => isset($postData['year_of_experience']) ? $postData['year_of_experience'] : $UserObj->year_of_experience,
            ];

            DB::beginTransaction();
            $response = $this->UserRep->CompleteProfile($update_profile, Auth::user()->id);
            DB::commit();

            $data = General::setResponse('SUCCESS', "Data updated successfully.");
            $data['data'] = $update_profile;
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function UpdateProfileImages($postData, $request)
    {
        try {

            $Image_ids = auth()->user()->profile_images->pluck('id');

            $deleteImages = $this->ProfileImgRep->DeleteOldImages($Image_ids);

            $ProfileImage = "";

            if (!empty($request->file('profile_image'))) {

                foreach (auth()->user()->profile_images as $key => $profile) {
                    if (Storage::exists('public/profile_image/' . $profile->profile_image)) {
                        Storage::delete('public/profile_image/' . $profile->profile_image);
                    }
                }

                foreach ($request->file('profile_image') as $key => $file) {
                    $ProfileImage   = rand() . time() . '.' . $file->getClientOriginalExtension();
                    $ProfileStore   = $this->ProfileImgRep->StoreProfileImage(auth()->user()->id, $ProfileImage);
                    if ($ProfileStore) {
                        $file->storeAs('public/profile_image', $ProfileImage);
                    }
                }
            }

            $profile_image              = [];

            $user = $this->UserRep->GetUser(Auth::user()->id);

            foreach ($user->profile_images as $key => $profile) {
                $pro = General::checkImage($profile->profile_image, 'user');
                $profile_image[] = $pro;
            }

            $data   = General::setResponse('SUCCESS', "Images Updated successfully.");
            $data['data'] = $profile_image;
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function Logout()
    {
        try {
            $user = Auth::user();
            $user->device_token = null;
            $user->platform = null;
            $user->save();
            $user->tokens()->delete();
            $data = General::setResponse('SUCCESS', "User logged out successfully.");
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }


    public function DeleteAccount()
    {
        try {

            $user = auth()->user();

            $delete = [
                'name' => 'deleted_user',
                'email' => $user->email . '-user_deleted',
                'profile_image' => $user->name,
                'device_token' => null,
                'api_token' => null
            ];

            $res = $this->UserRep->DeleteAccount($delete);

            $data = General::setResponse('SUCCESS', "User deleted successfully.");
            $data['data'] = (object)[];
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function ResetPassword($postData)
    {
        try {
            $credentials = [
                'email' => $postData['email'] ?? null,
                'password' => $postData['password'] ?? null,
                'password_confirmation' => $postData['password_confirmation'] ?? null,
                'token' => $postData['token'] ?? null,
            ];

            $status = Password::reset($credentials, function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });

            if ($status == Password::PASSWORD_RESET) {
                return General::setResponse('SUCCESS', "Password reset successfully.");
            } else {
                return General::setResponse('VALIDATION_ERROR', __($status));
            }
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
