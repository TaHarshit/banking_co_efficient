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
            if (Auth::guard('business')->attempt(['email' => $email, 'password' => $password], $remember)) {
                $business = Auth::guard('business')->user();

                if ($business->status != 1) {
                    Auth::guard('business')->logout();
                    return [
                        'success' => false,
                        'message' => 'Your account is inactive. Please contact administrator.'
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'business' => $business
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
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

    public function UpdateProfile($id, $name, $logo, $address)
    {
        try {
            $business = $this->BusinessRep->GetBusiness($id);

            if (!$business) {
                return [
                    'success' => false,
                    'message' => 'Business not found'
                ];
            }

            $this->BusinessRep->StoreBusiness($name, $business->email, $logo, $address, $business->status, $id);

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
