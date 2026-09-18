<?php

namespace App\Http\Controllers\Admin;

use App\Classes\Admin\BusinessCls;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BusinessController extends Controller
{
    protected $BusinessCls;

    public function __construct(BusinessCls $BusinessCls)
    {
        $this->BusinessCls = $BusinessCls;
    }

    public function ManageBusinesses()
    {
        $businesses = $this->BusinessCls->GetBusinesses();
        return view('businesses.manage', ['businesses' => $businesses, 'page_name' => 'Businesses']);
    }

    public function CreateBusiness()
    {
        $plans = \App\Models\Plans::where('status', 1)->get();
        return view('businesses.addedit', ['plans' => $plans, 'page_name' => 'Add Business']);
    }

    public function UpdateBusiness($id)
    {
        $data = $this->BusinessCls->GetBusiness($id);
        $plans = \App\Models\Plans::where('status', 1)->get();
        return view('businesses.addedit', ['data' => $data, 'plans' => $plans, 'page_name' => 'Edit Business']);
    }

    public function DeleteBusiness($id)
    {
        return $this->BusinessCls->DeleteBusiness($id);
    }

    public function StoreBusiness(Request $request)
    {
        $validatedData = $request->validate([
            'name'                    => 'required|max:255',
            'email'                   => 'required|email|max:255|unique:businesses,email' . ($request->id ? ",$request->id,id" : ',NULL,id'),
            'logo'                    => $request->hasFile('logo') ? 'image|mimes:jpg,jpeg,png|max:2048' : '',
            'address'                 => 'nullable|max:1000',
            'plan_id'                 => 'nullable|exists:plans,id',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date'   => 'nullable|date|after_or_equal:subscription_start_date',
            'user_quota'              => 'nullable|integer|min:0',
            'payment_mode'            => 'nullable|string|max:50',
            'payment_notes'           => 'nullable|string|max:2000',
        ]);

        $logo = $request->file('logo');
        $subscriptionData = [
            'plan_id'                 => $request->plan_id,
            'subscription_start_date' => $request->subscription_start_date,
            'subscription_end_date'   => $request->subscription_end_date,
            'user_quota'              => $request->user_quota,
            'payment_mode'            => $request->payment_mode ?? 'cash',
            'payment_notes'           => $request->payment_notes,
        ];

        return $this->BusinessCls->StoreBusiness(
            $request->name,
            $request->email,
            $logo,
            $request->address,
            $request->status ?? 1,
            $request->id ?? 0,
            $subscriptionData
        );
    }

    public function ChangeStatus(Request $request)
    {
        return $this->BusinessCls->ChangeStatus($request->id, $request->status);
    }

    public function ResendInvitation($id)
    {
        return $this->BusinessCls->ResendInvitation($id);
    }
}
