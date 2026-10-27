<?php

namespace App\Classes\Business;

use App\Repositories\Admin\BusinessRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Exception;

class BusinessCls
{

    protected $BusinessRep;

    public function __construct(BusinessRepository $BusinessRep)
    {
        $this->BusinessRep = $BusinessRep;
    }

    public function SetupPassword($token, $password)
    {
        try {
            $business = $this->BusinessRep->SetupPassword($token, $password);

            if (!$business) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ];
            }

            return [
                'success' => true,
                'message' => 'Password set up successfully',
                'business' => $business
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function Login($email, $password, $remember = false)
    {
        try {
            // 1. First attempt: Bank Admin (Business account)
            if (Auth::guard('business')->attempt(['email' => $email, 'password' => $password], $remember)) {
                $business = Auth::guard('business')->user();

                if ($business->status != 1) {
                    Auth::guard('business')->logout();
                    return [
                        'success' => false,
                        'message' => 'Your account is inactive. Please contact administrator.'
                    ];
                }

                session(['business_role' => 'admin']);
                session()->forget(['manager_id', 'manager_name', 'manager_email', 'manager_department']);

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'business' => $business
                ];
            }

            // 2. Second attempt: Bank Manager
            $manager = \App\Models\BankManager::with('business')->where('email', $email)->first();
            if ($manager && \Illuminate\Support\Facades\Hash::check($password, $manager->password)) {
                if ($manager->status != 1) {
                    return [
                        'success' => false,
                        'message' => 'Your manager account is inactive. Please contact your bank administrator.'
                    ];
                }

                if (!$manager->business || $manager->business->status != 1) {
                    return [
                        'success' => false,
                        'message' => 'Your bank account is inactive. Please contact administrator.'
                    ];
                }

                // Log into business guard with tenant business context
                Auth::guard('business')->login($manager->business, $remember);

                // Set Bank Manager session variables
                session([
                    'business_role'      => 'manager',
                    'manager_id'         => $manager->id,
                    'manager_name'       => $manager->name,
                    'manager_email'      => $manager->email,
                    'manager_department' => $manager->department,
                ]);

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'business' => $manager->business,
                    'is_manager' => true
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function GetProfile($businessId)
    {
        try {
            return $this->BusinessRep->GetBusiness($businessId);
        } catch (Exception $e) {
            return null;
        }
    }

    public function UpdateProfile($id, $name, $logo, $address, $contactEmail = null)
    {
        try {
            $business = $this->BusinessRep->GetBusiness($id);

            if (!$business) {
                return [
                    'success' => false,
                    'message' => 'Business not found'
                ];
            }

            $this->BusinessRep->StoreBusiness($name, $business->email, $logo, $address, $business->status, $id, [], $contactEmail);

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
