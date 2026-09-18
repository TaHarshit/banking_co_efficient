<?php

namespace App\Repositories\Api;

// use App\General\General;

use App\General\General;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;
// use Illuminate\Support\Facades\Auth as FacadesAuth;

class UserRepository extends BaseRepository
{

    public function model()
    {
        return User::class;
    }

    public function GetUser($id)
    {
        return $this->model->find($id);
    }

    public function GetUserByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function UpdateDeviceToken($token, $platform, $id)
    {
        return $this->model->where('id', $id)->update(['device_token' => $token, 'platform' => $platform]);
    }

    public function UpdateApiToken($token, $id)
    {
        return $this->model->where('id', $id)->update(['api_token' => hash('sha256', $token)]);
    }

    public function Signup($postData)
    {
        return $this->model->create([
            'name'                  => $postData['name'],
            'username'              => $postData['username'],
            'email'                 => $postData['email'],
            'password'              => Hash::make($postData['password']),
            'user_type'             => '2',
            'device_token'          => $postData['device_token'],
            'business_id'           => $postData['business_id'] ?? null,
            'status'                => $postData['status'] ?? 'active',
            'subscribe_newsletter'  => $postData['subscribe_newsletter'] ?? false,
        ]);
    }

    public function UpdateSocialId($filed_name, $value, $id)
    {
        return $this->model->where('id', $id)->update(["$filed_name" => $value]);
    }

    public function GetUserBySocialId($apple_id, $google_id, $email)
    {
        $que = $this->model;
        if ($apple_id != "") {
            $que = $que->where('apple_id', $apple_id)->orWhere('email', $email);
        } elseif ($google_id != "") {
            $que = $que->where('google_id', $google_id)->orWhere('email', $email);
        } else {
            return General::setResponse('VALIDATION_ERROR', __('messages.invalid_social_id'));
        }
        return $que->first();
    }

    public function CompleteProfile($update_profile, $user_id)
    {
        return $this->model->find($user_id)->update($update_profile);
    }

    public function ChangePassword($NewPassword)
    {
        return $this->model->where('id', auth()->user()->id)->update(['password' => Hash::make($NewPassword)]);
    }

    public function GetUsersList($user_list)
    {
        return $this->model->whereIn('id', $user_list)->get();
    }

    public function DeleteAccount($delete)
    {
        return $this->model->where('id', auth()->user()->id)->update($delete);
    }

    public function UpdateSubscription($plan_id)
    {
        // In banking_co_efficient, subscriptions are tracked via user_subscriptions / plans.
        // There are no post_count / spotlight columns on the users table.
        return true;
    }

    public function UpdateUsage($post_count, $post_spotlight_count, $apply_count, $apply_spotlight_count)
    {

        $getUser = $this->model->find(auth()->id());
        return $getUser->update([
            'post_count' => ($getUser->post_count + $post_count),
            'post_spotlight_count' => ($getUser->post_spotlight_count + $post_spotlight_count),
            'apply_count' => ($getUser->apply_count + $apply_count),
            'apply_spotlight_count' => ($getUser->apply_spotlight_count + $apply_spotlight_count)
        ]);
    }

    public function UpdateUsageByUser($post_count, $post_spotlight_count, $apply_count, $apply_spotlight_count, $user_id)
    {

        $getUser = $this->model->find($user_id);
        return $getUser->update([
            'post_count' => ($getUser->post_count + $post_count),
            'post_spotlight_count' => ($getUser->post_spotlight_count + $post_spotlight_count),
            'apply_count' => ($getUser->apply_count + $apply_count),
            'apply_spotlight_count' => ($getUser->apply_spotlight_count + $apply_spotlight_count)
        ]);
    }
}
