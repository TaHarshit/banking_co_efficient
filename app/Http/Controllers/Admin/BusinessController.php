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
        return view('businesses.addedit', ['page_name' => 'Add Business']);
    }

    public function UpdateBusiness($id)
    {
        $data = $this->BusinessCls->GetBusiness($id);
        return view('businesses.addedit', ['data' => $data, 'page_name' => 'Edit Business']);
    }

    public function DeleteBusiness($id)
    {
        return $this->BusinessCls->DeleteBusiness($id);
    }

    public function StoreBusiness(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:businesses,email' . ($request->id ? ",$request->id,id" : ',NULL,id'),
            'logo' => $request->hasFile('logo') ? 'image|mimes:jpg,jpeg,png|max:2048' : '',
            'address' => 'nullable|max:1000',
        ]);

        $logo = $request->file('logo');
        return $this->BusinessCls->StoreBusiness(
            $request->name,
            $request->email,
            $logo,
            $request->address,
            $request->status ?? 1,
            $request->id ?? 0
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
