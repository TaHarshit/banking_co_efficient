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

    public function StoreBusiness($name, $email, $logo, $address, $status, $id)
    {
        $data = [];
        $data['name'] = $name;
        $data['email'] = $email;
        $data['address'] = $address;
        $data['status'] = $status;

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
                $data['logo'] = $businessObj->logo;
            }
        }

        if ($id > 0) {
            $update = $this->model->where('id', $id)->update($data);
            if ($update) {
                logAdminActivity('Business', 'Update', $id, "Updated business: $name", $data);
            }
            return $update;
        } else {
            $business = $this->model->create($data);
            if ($business) {
                logAdminActivity('Business', 'Add', $business->id, "Added new business: $name", $data);
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
