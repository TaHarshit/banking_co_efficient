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

        // Check if subscription is active
        if (!$business->isSubscriptionActive()) {
            Session::flash('message', 'Cannot approve user: Your business subscription is not active or has expired. Please contact admin.');
            Session::flash('icon', 'error');
            return redirect()->route('business.users.pending');
        }

        $user = User::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (!$user) {
            Session::flash('message', 'User not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.users.pending');
        }

        // If user is not already an employee, check remaining quota
        $isExistingEmployee = \App\Models\Employee::where('business_id', $business->id)
            ->where('email', strtolower($user->email))
            ->exists();

        if (!$isExistingEmployee && $business->getRemainingQuota() <= 0) {
            Session::flash('message', 'Cannot approve user: Quota limit of ' . ($business->user_quota ?? 0) . ' seats reached. Please contact admin to increase quota.');
            Session::flash('icon', 'error');
            return redirect()->route('business.users.pending');
        }

        $user->status = 'active'; // active status
        $user->save();

        logBusinessActivity('Users', 'Approve', $user->id, "Approved user: {$user->name} {$user->surname} ({$user->email})", null, $business->id);

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

        $userName = "{$user->name} {$user->surname} ({$user->email})";

        // Also remove the employee record if it exists
        \App\Models\Employee::where('business_id', $business->id)
            ->where('email', strtolower($user->email))
            ->delete();

        $user->status = 2;
        $user->business_id = null;
        $user->save();

        logBusinessActivity('Users', 'Reject', $id, "Rejected user: {$userName}", null, $business->id);

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

        $userName = "{$user->name} {$user->surname} ({$user->email})";

        // Also remove the employee record if it exists
        \App\Models\Employee::where('business_id', $business->id)
            ->where('email', strtolower($user->email))
            ->delete();

        $user->business_id = null;
        $user->status = 1;
        $user->save();

        logBusinessActivity('Users', 'Remove', $id, "Removed user: {$userName} from business", null, $business->id);

        Session::flash('message', 'User removed from business');
        Session::flash('icon', 'success');
        return redirect()->route('business.users');
    }

    /**
     * Handle bulk actions for business users
     */
    public function BulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:remove,approve,reject',
            'user_ids' => 'required',
        ]);

        $business = Auth::guard('business')->user();
        $userIds = $request->user_ids;
        if (is_string($userIds)) {
            $decoded = json_decode($userIds, true);
            $userIds = is_array($decoded) ? $decoded : array_filter(explode(',', $userIds));
        }

        $userIds = array_values(array_filter((array)$userIds, function ($id) {
            return (int)$id > 0;
        }));

        if (empty($userIds)) {
            Session::flash('message', 'No users selected');
            Session::flash('icon', 'warning');
            return redirect()->back();
        }

        $action = $request->action;
        $count = 0;

        if ($action === 'remove') {
            $users = User::whereIn('id', $userIds)
                ->where('business_id', $business->id)
                ->get();

            foreach ($users as $user) {
                \App\Models\Employee::where('business_id', $business->id)
                    ->where('email', strtolower($user->email))
                    ->delete();

                $user->business_id = null;
                $user->status = 1;
                $user->save();

                logBusinessActivity('Users', 'Remove', $user->id, "Removed user: {$user->name} {$user->surname} ({$user->email}) from business", null, $business->id);
                $count++;
            }

            Session::flash('message', "$count user(s) removed from business successfully");
            Session::flash('icon', 'success');
        } elseif ($action === 'approve') {
            if (!$business->isSubscriptionActive()) {
                Session::flash('message', 'Cannot approve users: Business subscription is not active or has expired.');
                Session::flash('icon', 'error');
                return redirect()->back();
            }

            $users = User::whereIn('id', $userIds)
                ->where('business_id', $business->id)
                ->get();

            foreach ($users as $user) {
                $isExistingEmployee = \App\Models\Employee::where('business_id', $business->id)
                    ->where('email', strtolower($user->email))
                    ->exists();

                if (!$isExistingEmployee && $business->getRemainingQuota() <= 0) {
                    break;
                }

                $user->status = 'active';
                $user->save();
                logBusinessActivity('Users', 'Approve', $user->id, "Approved user: {$user->name} {$user->surname} ({$user->email})", null, $business->id);
                $count++;
            }

            Session::flash('message', "$count user(s) approved successfully");
            Session::flash('icon', 'success');
        } elseif ($action === 'reject') {
            $users = User::whereIn('id', $userIds)
                ->where('business_id', $business->id)
                ->get();

            foreach ($users as $user) {
                \App\Models\Employee::where('business_id', $business->id)
                    ->where('email', strtolower($user->email))
                    ->delete();

                $user->status = 2;
                $user->business_id = null;
                $user->save();

                logBusinessActivity('Users', 'Reject', $user->id, "Rejected user: {$user->name} {$user->surname} ({$user->email})", null, $business->id);
                $count++;
            }

            Session::flash('message', "$count user(s) rejected successfully");
            Session::flash('icon', 'success');
        }

        return redirect()->back();
    }

}
