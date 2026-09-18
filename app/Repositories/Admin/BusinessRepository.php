<?php

namespace App\Repositories\Admin;

use App\Models\Business;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class BusinessRepository extends BaseRepository
{

    public function model()
    {
        return Business::class;
    }

    public function GetBusinesses()
    {
        return $this->model->orderBy('created_at', 'desc')->get();
    }

    public function GetBusiness($id)
    {
        return $this->model->find($id);
    }

    public function GetBusinessByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function GetBusinessByToken($token)
    {
        return $this->model
            ->where('password_setup_token', $token)
            ->where('password_setup_token_expires_at', '>', now())
            ->first();
    }

    public function StoreBusiness($name, $email, $logo, $address, $status, $id, $subscriptionData = [])
    {
        $data = [];
        $data['name'] = $name;
        $data['email'] = $email;
        $data['address'] = $address;
        $data['status'] = $status;

        // Subscription & Quota fields
        if (array_key_exists('plan_id', $subscriptionData)) {
            $data['plan_id'] = !empty($subscriptionData['plan_id']) ? $subscriptionData['plan_id'] : null;
        }
        if (array_key_exists('subscription_start_date', $subscriptionData)) {
            $data['subscription_start_date'] = !empty($subscriptionData['subscription_start_date']) ? $subscriptionData['subscription_start_date'] : null;
        }
        if (array_key_exists('subscription_end_date', $subscriptionData)) {
            $data['subscription_end_date'] = !empty($subscriptionData['subscription_end_date']) ? $subscriptionData['subscription_end_date'] : null;
        }
        if (array_key_exists('user_quota', $subscriptionData)) {
            $data['user_quota'] = isset($subscriptionData['user_quota']) ? (int)$subscriptionData['user_quota'] : 0;
        }
        if (array_key_exists('payment_mode', $subscriptionData)) {
            $data['payment_mode'] = !empty($subscriptionData['payment_mode']) ? $subscriptionData['payment_mode'] : 'cash';
        }
        if (array_key_exists('payment_notes', $subscriptionData)) {
            $data['payment_notes'] = $subscriptionData['payment_notes'] ?? null;
        }

        // Handle logo upload
        if (!empty($logo)) {
            if ($id > 0) {
                $oldBusiness = $this->GetBusiness($id);
                if ($oldBusiness && $oldBusiness->logo && Storage::exists('public/business_logos/' . $oldBusiness->logo)) {
                    Storage::delete('public/business_logos/' . $oldBusiness->logo);
                }
            }

            $logoName = rand() . time() . '.' . $logo->getClientOriginalExtension();
            $logo->storeAs('public/business_logos', $logoName);
            $data['logo'] = $logoName;
        } else {
            if ($id > 0) {
                $businessObj = $this->GetBusiness($id);
                $data['logo'] = $businessObj->logo ?? null;
            }
        }

        if ($id > 0) {
            $update = $this->model->where('id', $id)->update($data);
            if ($update) {
                logAdminActivity('Business', 'Update', $id, "Updated business: $name", $data);

                // Record subscription history if plan and dates provided
                if (!empty($data['plan_id']) && !empty($data['subscription_end_date'])) {
                    \App\Models\UserSubscriptions::create([
                        'business_id'             => $id,
                        'user_name'               => $data['name'],
                        'user_email'              => $data['email'],
                        'plan_id'                 => $data['plan_id'],
                        'purchase_from'           => $data['payment_mode'] ?? 'cash',
                        'subscription_start_date' => $data['subscription_start_date'] ?? now(),
                        'subscription_end_date'   => $data['subscription_end_date'],
                        'user_quota'              => $data['user_quota'] ?? 0,
                        'status'                  => 1,
                        'notes'                   => $data['payment_notes'] ?? null,
                    ]);
                }
            }
            return $update;
        } else {
            $business = $this->model->create($data);
            if ($business) {
                logAdminActivity('Business', 'Add', $business->id, "Added new business: $name", $data);

                // Record subscription history if plan and dates provided
                if (!empty($data['plan_id']) && !empty($data['subscription_end_date'])) {
                    \App\Models\UserSubscriptions::create([
                        'business_id'             => $business->id,
                        'user_name'               => $data['name'],
                        'user_email'              => $data['email'],
                        'plan_id'                 => $data['plan_id'],
                        'purchase_from'           => $data['payment_mode'] ?? 'cash',
                        'subscription_start_date' => $data['subscription_start_date'] ?? now(),
                        'subscription_end_date'   => $data['subscription_end_date'],
                        'user_quota'              => $data['user_quota'] ?? 0,
                        'status'                  => 1,
                        'notes'                   => $data['payment_notes'] ?? null,
                    ]);
                }
            }
            return $business;
        }
    }

    public function SetupPassword($token, $password)
    {
        $business = $this->GetBusinessByToken($token);

        if (!$business) {
            return false;
        }

        $business->password = $password;
        $business->password_setup_token = null;
        $business->password_setup_token_expires_at = null;
        $business->save();

        return $business;
    }

    public function ChangeStatus($id, $status)
    {
        $update = $this->model->where('id', $id)->update(['status' => $status]);
        if ($update) {
            $statusText = $status == 1 ? 'Active' : 'Inactive';
            logAdminActivity('Business', 'Status Change', $id, "Changed business status to: $statusText");
        }
        return $update;
    }

    public function DeleteBusiness($id)
    {
        $business = $this->GetBusiness($id);
        $name = $business ? $business->name : "ID: $id";

        if ($business && $business->logo && Storage::exists('public/business_logos/' . $business->logo)) {
            Storage::delete('public/business_logos/' . $business->logo);
        }

        $delete = $this->model->where('id', $id)->delete();
        if ($delete) {
            logAdminActivity('Business', 'Delete', $id, "Deleted business: $name");
        }
        return $delete;
    }
}
