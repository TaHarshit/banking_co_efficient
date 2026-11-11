<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Repositories\Admin\UserRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserCls
{

    protected $UserRep;
    protected $ProfileImgRep;

    public function __construct(UserRepository $UserRep)
    {
        $this->UserRep = $UserRep;
    }

    public function Signin($postData)
    {
        try {
            $remember_me = $postData->has('remember_me') ? true : false;
            if (Auth::attempt(['email' => $postData->email, 'password' => $postData->password], $remember_me)) {

                if (Auth::user()->user_type == 1) {
                    if (!in_array(Auth::user()->status, [1, '1', 'active'], false)) {
                        auth()->guard('web')->logout();
                        Session::flash('message', 'Your account is inactive.');
                        Session::flash('icon', 'warning');
                        return redirect()->route('login');
                    }
                    return redirect()->route('dashboard');
                } else {
                    auth()->guard('web')->logout();
                    Session::flash('message', 'Access denied. Only administrators can log in to the admin panel.');
                    Session::flash('icon', 'danger');
                    return redirect()->route('login');
                }
            } else {
                Session::flash('message', 'Username/Password is wrong');
                Session::flash('icon', 'warning');
                return redirect()->route('login');
            }
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function GetUser($id)
    {
        try {
            return $this->UserRep->GetUser($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function GetUsers($filter = '')
    {
        try {
            return $this->UserRep->GetUsers($filter);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function ExportUsers($filter = '')
    {
        try {
            $users = $this->UserRep->GetUsers($filter);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'ID' => 'id',
                'User Type' => 'user_type',
                'First Name' => 'name',
                'Last Name' => 'surname',
                'Email' => 'email',
                'Phone Number' => 'phone_no',
                'Job Title' => 'job_title',
                'Institution' => 'institution',
                'Department' => 'department',
                'Years of Experience' => 'year_of_experience',
                'Joining Date' => 'created_at',
            ];

            $col = 1;
            foreach ($headers as $label => $key) {
                $columnString = Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($columnString . '1', $label);
                $sheet->getColumnDimension($columnString)->setAutoSize(true);
                $col++;
            }

            $row = 2;
            foreach ($users as $user) {
                $col = 1;

                $userType = '';
                if ($user->user_type == 1) {
                    $userType = 'Admin';
                } elseif (empty($user->business_id)) {
                    $userType = 'Individual';
                } else {
                    $userType = 'Company: ' . ($user->business ? $user->business->name : 'N/A');
                }

                $data = [
                    'id' => $user->id,
                    'user_type' => $userType,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'phone_no' => $user->phone_no,
                    'job_title' => $user->job_title,
                    'institution' => $user->institution,
                    'department' => $user->department,
                    'year_of_experience' => $user->year_of_experience,
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d') : '',
                ];

                foreach ($headers as $label => $key) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $data[$key]);
                    $col++;
                }
                $row++;
            }

            $filterName = empty($filter) ? 'all' : ($filter === 'individual' ? 'individual' : 'company_' . $filter);
            $fileName = 'users_' . $filterName . '_' . date('Y-m-d') . '.xlsx';

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            Session::flash('message', 'Something went wrong: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    public function GetAdmins()
    {
        try {
            return $this->UserRep->GetAdmins();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function DeleteUser($id)
    {
        try {
            if (Auth::id() == $id) {
                Session::flash('message', 'You cannot delete your own account');
                Session::flash('icon', 'warning');
                return redirect()->route('manageusers');
            }

            $this->UserRep->deleteUser($id);
            Session::flash('message', 'User deleted successfully');
            Session::flash('icon', 'success');
            return redirect()->route('manageusers');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function BulkAction(string $action, array $userIds, $businessId = null, $status = null)
    {
        try {
            $currentUserId = Auth::id();
            // Filter out current logged in user so they cannot delete or alter themselves
            $userIds = array_values(array_filter($userIds, function ($id) use ($currentUserId) {
                return (int)$id > 0 && (int)$id !== (int)$currentUserId;
            }));

            if (empty($userIds)) {
                Session::flash('message', 'No valid users selected');
                Session::flash('icon', 'warning');
                return redirect()->back();
            }

            if ($action === 'delete') {
                $count = 0;
                foreach ($userIds as $id) {
                    if ($this->UserRep->deleteUser($id)) {
                        $count++;
                    }
                }
                Session::flash('message', "$count user(s) deleted successfully");
                Session::flash('icon', 'success');
            } elseif ($action === 'assign_company') {
                $business = !empty($businessId) ? \App\Models\Business::find($businessId) : null;
                $businessName = $business ? $business->name : 'Individual';

                $count = 0;
                foreach ($userIds as $id) {
                    $user = $this->UserRep->GetUser($id);
                    if ($user && (int)$user->user_type !== 1) { // Do not assign admin to business
                        // If user was previously assigned to a different business, clean up old employee record
                        if ($user->business_id && (int)$user->business_id !== (int)$businessId) {
                            \App\Models\Employee::where('business_id', $user->business_id)
                                ->where('email', strtolower($user->email))
                                ->delete();
                        }

                        // If assigning to a business, ensure employee record exists
                        if (!empty($businessId)) {
                            $exists = \App\Models\Employee::where('business_id', $businessId)
                                ->where('email', strtolower($user->email))
                                ->exists();
                            if (!$exists) {
                                \App\Models\Employee::create([
                                    'business_id' => $businessId,
                                    'name' => $user->name . ($user->surname ? ' ' . $user->surname : ''),
                                    'email' => strtolower($user->email),
                                    'department' => $user->department,
                                    'phone' => $user->phone_no,
                                ]);
                            }
                        }

                        $user->business_id = !empty($businessId) ? $businessId : null;
                        $user->save();
                        $count++;
                    }
                }
                Session::flash('message', "$count user(s) updated to company: $businessName");
                Session::flash('icon', 'success');
            } elseif ($action === 'change_status') {
                $statusVal = (int)$status;
                $count = 0;
                foreach ($userIds as $id) {
                    if ($this->UserRep->ChangeStatus($id, $statusVal)) {
                        $count++;
                    }
                }
                $statusLabel = $statusVal === 1 ? 'activated' : 'deactivated';
                Session::flash('message', "$count user(s) $statusLabel successfully");
                Session::flash('icon', 'success');
            }

            return redirect()->back();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function updateProfile($request, $image, $id)
    {
        try {

            // if(!empty($image)){

            //     if(Storage::exists('public/profile_image/'.Auth::user()->profile_image)){
            //         Storage::delete('public/profile_image/'.Auth::user()->profile_image);
            //     }

            //     $ImageName = rand().time().'.'.$image->getClientOriginalExtension();  
            //     $image->storeAs('public/profile_image', $ImageName);

            // } else {
            //     $ImageName = Auth::user()->profile_image;
            // }

            $this->UserRep->updateProfile(
                $request->name, 
                $request->surname, 
                $request->email, 
                $request->phone_no, 
                $id,
                $request->username,
                $request->job_title,
                $request->institution,
                $request->department,
                $request->year_of_experience
            );
            
            if (trim($request->password) != "" && $id > 0) {
                $this->UserRep->changePassword($request->password, $id);
            }

            Session::flash('message', 'Profile edited successfully');
            Session::flash('icon', 'success');
            return redirect()->route('setting');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function StoreUser($name, $surname, $email, $password, $phone_no, $status, $id, $username = null, $job_title = null, $institution = null, $department = null, $year_of_experience = null, $business_id = null, $user_type = 2)
    {

        try {
            // $ImageName  = "";
            // if(!empty($profile_image)){
            //     if($id>0){
            //         $OldProObj = $this->UserRep->GetUser($id);
            //         if(Storage::exists('public/profile_image/'.$OldProObj->profile_image)){
            //             Storage::delete('public/profile_image/'.$OldProObj->profile_image);
            //         }
            //     }

            //     $ImageName = rand().time().'.'.$profile_image->getClientOriginalExtension();
            //     $profile_image->storeAs('public/profile_image', $ImageName);

            // } else {
            //     if($id>0){
            //         $UserObj    = $this->UserRep->GetUser($id);
            //         $ImageName  = $UserObj->profile_image;
            //     }
            // }
            $response   = $this->UserRep->StoreUser($name, $surname, $email, $password, $phone_no, $status, $id, $username, $job_title, $institution, $department, $year_of_experience, $business_id, $user_type);
            
            if ($id <= 0 && $response) {
                // Generate password reset token
                $token = Password::createToken($response);
                
                // Send welcome email
                try {
                    Mail::to($email)->send(new \App\Mail\UserWelcomeMail($response, $password, $token));
                } catch (\Exception $e) {
                    // Log the error but continue
                    \Illuminate\Support\Facades\Log::error("Failed to send welcome email to $email: " . $e->getMessage());
                }
            }

            $entityType = ($user_type == 1) ? 'Admin' : 'User';
            $message    = ($id > 0) ? "$entityType updated successfully" : "$entityType added successfully and welcome email sent";
            Session::flash('message', $message);
            Session::flash('icon', 'success');
            return redirect()->route('manageusers');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function ChangeStatus($id, $status)
    {
        try {
            return $this->UserRep->ChangeStatus($id, $status);
        } catch (Exception $e) {
            return response()->view('backend.error.500', ['message' => $e->getMessage()], 500);
        }
    }
}
