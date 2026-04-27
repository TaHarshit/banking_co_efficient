<?php

namespace App\Repositories\Admin;

// use App\General\General;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;

class UserRepository extends BaseRepository
{

    public function model()
    {
        return User::class;
    }

    public function GetUsers($filter = '')
    {
        $query = $this->model->where('user_type', 2);

        if ($filter === 'individual') {
            $query->whereNull('business_id');
        } elseif (!empty($filter)) {
            $query->where('business_id', $filter);
        }

        return $query->get();
    }

    public function GetAdmins()
    {
        return $this->model->where('user_type', 1)->get();
    }

    public function GetUser($id)
    {
        return $this->model->find($id);
    }

    public function GetUserByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function StoreUser($name, $surname, $email, $password, $phone_no, $status, $id, $username = null, $job_title = null, $institution = null, $department = null, $year_of_experience = null, $business_id = null)
    {
        $data                   = [];
        $data['name']           = $name;
        $data['surname']        = $surname;
        $data['phone_no']       = $phone_no;
        $data['email']          = $email;
        $data['user_type']      = '2';
        $data['status']         = $status;
        $data['username']       = $username;
        $data['job_title']      = $job_title;
        $data['institution']    = $institution;
        $data['department']     = $department;
        $data['year_of_experience'] = $year_of_experience;
        $data['business_id']    = $business_id;

        if (!empty($password)) {
            $data['password'] = Hash::make($password);
        }

        if ($id > 0) {
            $update = $this->model->where('id', $id)->update($data);
            if ($update) {
                logAdminActivity('User', 'Update', $id, "Updated user: $name $surname", $data);
            }
            return $update;
        } else {
            $user = $this->model->create($data);
            if ($user) {
                logAdminActivity('User', 'Add', $user->id, "Added new user: $name $surname", $data);
            }
            return $user;
        }
    }

    public function updateProfile($name, $surname, $email, $phone_no, $id, $username = null, $job_title = null, $institution = null, $department = null, $year_of_experience = null)
    {

        return $this->model
            ->where('id', $id)
            ->update([
                'name'          => $name,
                'surname'       => $surname,
                'email'         => $email,
                'phone_no'      => $phone_no,
                'username'      => $username,
                'job_title'     => $job_title,
                'institution'   => $institution,
                'department'    => $department,
                'year_of_experience' => $year_of_experience,
            ]);
    }

    public function ChangeStatus($id, $status)
    {
        $update = $this->model->where('id', $id)->update(['status' => $status]);
        if ($update) {
            logAdminActivity('User', 'Status Change', $id, "Changed user status to: $status");
        }
        return $update;
    }

    public function deleteUser($id)
    {
        $user = $this->model->find($id);
        $name = $user ? "$user->name $user->surname" : "ID: $id";
        $delete = $this->model->where('id', $id)->delete();
        if ($delete) {
            logAdminActivity('User', 'Delete', $id, "Deleted user: $name");
        }
        return $delete;
    }

    public function changePassword($password, $id)
    {
        $update = $this->model->where('id', $id)->update([
            'password' => Hash::make($password)
        ]);
        if ($update) {
            logAdminActivity('User', 'Password Change', $id, "Changed password for user ID: $id");
        }
        return $update;
    }
}
