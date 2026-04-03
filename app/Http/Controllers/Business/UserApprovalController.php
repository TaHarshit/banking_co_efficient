<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserApprovalController extends Controller
{
    /**
     * Display pending users
     */
    public function PendingUsers()
    {
        $business = Auth::guard('business')->user();
        $pendingUsers = User::where('business_id', $business->id)
            ->where('status', 2)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('business.users.pending', [
            'users' => $pendingUsers,
            'business' => $business,
        ]);
    }

    /**
     * Display all users
     */
    public function AllUsers()
    {
        $business = Auth::guard('business')->user();
        $users = User::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('business.users.all', [
            'users' => $users,
            'business' => $business,
        ]);
    }

    /**
     * Approve user
     */
    public function Approve($id)
    {
        $business = Auth::guard('business')->user();
        $user = User::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (!$user) {
            Session::flash('message', 'User not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.users.pending');
        }

        $user->status = 1;
        $user->save();

        Session::flash('message', 'User approved successfully');
        Session::flash('icon', 'success');
        return redirect()->route('business.users.pending');
    }

    /**
     * Reject user
     */
    public function Reject($id)
    {
        $business = Auth::guard('business')->user();
        $user = User::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (!$user) {
            Session::flash('message', 'User not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.users.pending');
        }

        $user->status = 2;
        $user->business_id = null;
        $user->save();

        Session::flash('message', 'User rejected');
        Session::flash('icon', 'success');
        return redirect()->route('business.users.pending');
    }

    /**
     * Remove user from business
     */
    public function Remove($id)
    {
        $business = Auth::guard('business')->user();
        $user = User::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (!$user) {
            Session::flash('message', 'User not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.users');
        }

        $user->business_id = null;
        $user->status = 1;
        $user->save();

        Session::flash('message', 'User removed from business');
        Session::flash('icon', 'success');
        return redirect()->route('business.users');
    }
}
