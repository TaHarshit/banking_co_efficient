<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BankManager;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class BankManagerController extends Controller
{
    private function getBusinessId(): int
    {
        return (int) Auth::guard('business')->id();
    }

    /**
     * Display listing of bank managers
     */
    public function Index(Request $request)
    {
        $businessId = $this->getBusinessId();
        $business = Auth::guard('business')->user();

        $query = BankManager::where('business_id', $businessId)->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $managers = $query->paginate(15)->appends($request->all());

        // Get distinct departments across employees and users of this bank for convenience
        $departments = Employee::where('business_id', $businessId)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->merge(
                User::where('business_id', $businessId)
                    ->whereNotNull('department')
                    ->where('department', '!=', '')
                    ->distinct()
                    ->pluck('department')
            )
            ->merge(
                BankManager::where('business_id', $businessId)
                    ->whereNotNull('department')
                    ->where('department', '!=', '')
                    ->distinct()
                    ->pluck('department')
            )
            ->unique()
            ->sort()
            ->values();

        return view('business.managers.manage', compact('managers', 'departments', 'business'));
    }

    /**
     * Store a new bank manager
     */
    public function Store(Request $request)
    {
        $businessId = $this->getBusinessId();

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:bank_managers,email',
            'password'   => 'required|string|min:6',
            'phone'      => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
        ]);

        $manager = BankManager::create([
            'business_id' => $businessId,
            'name'        => trim($request->name),
            'email'       => strtolower(trim($request->email)),
            'password'    => Hash::make($request->password),
            'phone'       => $request->phone ? trim($request->phone) : null,
            'department'  => $request->department ? trim($request->department) : null,
            'status'      => 1,
        ]);

        logBusinessActivity('Managers', 'Create', $manager->id, "Created bank manager: {$manager->name} ({$manager->email})", null, $businessId);

        Session::flash('message', __('messages.manager_created_successfully') ?? 'Bank Manager created successfully');
        Session::flash('icon', 'success');

        return redirect()->route('business.managers');
    }

    /**
     * Update an existing bank manager
     */
    public function Update(Request $request, $id)
    {
        $businessId = $this->getBusinessId();
        $manager = BankManager::where('business_id', $businessId)->findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => "required|email|max:255|unique:bank_managers,email,{$id},id",
            'password'   => 'nullable|string|min:6',
            'phone'      => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'status'     => 'nullable|in:0,1',
        ]);

        $manager->name = trim($request->name);
        $manager->email = strtolower(trim($request->email));
        $manager->phone = $request->phone ? trim($request->phone) : null;
        $manager->department = $request->department ? trim($request->department) : null;

        if ($request->filled('status')) {
            $manager->status = (int) $request->status;
        }

        if ($request->filled('password')) {
            $manager->password = Hash::make($request->password);
        }

        $manager->save();

        logBusinessActivity('Managers', 'Update', $manager->id, "Updated bank manager: {$manager->name} ({$manager->email})", null, $businessId);

        Session::flash('message', __('messages.manager_updated_successfully') ?? 'Bank Manager updated successfully');
        Session::flash('icon', 'success');

        return redirect()->route('business.managers');
    }

    /**
     * Toggle manager status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $businessId = $this->getBusinessId();

        $request->validate([
            'id'     => 'required|exists:bank_managers,id',
            'status' => 'required|in:0,1',
        ]);

        $manager = BankManager::where('business_id', $businessId)->findOrFail($request->id);
        $manager->status = (int) $request->status;
        $manager->save();

        logBusinessActivity('Managers', 'Status', $manager->id, "Toggled bank manager status to " . ($manager->status == 1 ? 'Active' : 'Inactive'), null, $businessId);

        return response()->json(['status' => 1]);
    }

    /**
     * Delete a bank manager
     */
    public function Delete($id)
    {
        $businessId = $this->getBusinessId();
        $manager = BankManager::where('business_id', $businessId)->findOrFail($id);
        $managerName = "{$manager->name} ({$manager->email})";

        $manager->delete();

        logBusinessActivity('Managers', 'Delete', $id, "Deleted bank manager: {$managerName}", null, $businessId);

        Session::flash('message', __('messages.manager_deleted_successfully') ?? 'Bank Manager deleted successfully');
        Session::flash('icon', 'success');

        return redirect()->route('business.managers');
    }
}
