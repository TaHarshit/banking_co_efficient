<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Repositories\Admin\BusinessRepository;
use App\Mail\DailyMail;
use Exception;

class BusinessCls
{

    protected $BusinessRep;

    public function __construct(BusinessRepository $BusinessRep)
    {
        $this->BusinessRep = $BusinessRep;
    }

    public function GetBusinesses()
    {
        try {
            return $this->BusinessRep->GetBusinesses();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function GetBusiness($id)
    {
        try {
            return $this->BusinessRep->GetBusiness($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function DeleteBusiness($id)
    {
        try {
            $this->BusinessRep->DeleteBusiness($id);
            Session::flash('message', 'Business deleted successfully');
            Session::flash('icon', 'success');
            return redirect()->route('managebusinesses');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function StoreBusiness($name, $email, $logo, $address, $status, $id)
    {
        try {
            $response = $this->BusinessRep->StoreBusiness($name, $email, $logo, $address, $status, $id);

            // If new business, generate token and send invitation email
            if ($id <= 0 && $response) {
                $business = $this->BusinessRep->GetBusinessByEmail($email);
                if ($business) {
                    $this->SendInvitationEmail($business);
                }
            }

            $message = ($id > 0) ? 'Business updated successfully' : 'Business added successfully';
            Session::flash('message', $message);
            Session::flash('icon', 'success');
            return redirect()->route('managebusinesses');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function SendInvitationEmail($business)
    {
        try {
            // Generate password setup token
            $token = $business->generatePasswordSetupToken();

            // Prepare email data
            $data = [
                'business_name' => $business->name,
                'business_email' => $business->email,
                'setup_link' => route('business.password.setup', ['token' => $token]),
            ];

            // Send email
            Mail::to($business->email)->send(
                new DailyMail(
                    'Welcome to ' . config('app.name') . ' - Setup Your Account',
                    'emails.business-invitation',
                    $data
                )
            );

            return true;
        } catch (Exception $e) {
            // Log error but don't fail the business creation
            Log::error('Failed to send business invitation email: ' . $e->getMessage());
            return false;
        }
    }

    public function ChangeStatus($id, $status)
    {
        try {
            return $this->BusinessRep->ChangeStatus($id, $status);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    public function ResendInvitation($id)
    {
        try {
            $business = $this->BusinessRep->GetBusiness($id);

            if (!$business) {
                Session::flash('message', 'Business not found');
                Session::flash('icon', 'error');
                return redirect()->route('managebusinesses');
            }

            // Check if password is already set
            if (!empty($business->password)) {
                Session::flash('message', 'Business has already set up their password');
                Session::flash('icon', 'warning');
                return redirect()->route('managebusinesses');
            }

            // Send invitation email
            $this->SendInvitationEmail($business);

            Session::flash('message', 'Invitation email resent successfully');
            Session::flash('icon', 'success');
            return redirect()->route('managebusinesses');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }
}
