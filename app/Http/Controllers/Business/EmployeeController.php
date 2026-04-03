<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Repositories\Business\EmployeeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeController extends Controller
{
    protected $EmployeeRep;

    public function __construct(EmployeeRepository $EmployeeRep)
    {
        $this->EmployeeRep = $EmployeeRep;
    }

    /**
     * Display employee list
     */
    public function Index()
    {
        $business = Auth::guard('business')->user();
        $employees = $this->EmployeeRep->GetEmployees($business->id);

        return view('business.employees.manage', [
            'employees' => $employees,
            'business' => $business,
        ]);
    }

    /**
     * Show create form
     */
    public function Create()
    {
        $business = Auth::guard('business')->user();
        return view('business.employees.addedit', [
            'business' => $business,
        ]);
    }

    /**
     * Store new employee
     */
    public function Store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $business = Auth::guard('business')->user();

        // Check if email already exists
        if ($this->EmployeeRep->IsEmployee($business->id, $request->email)) {
            Session::flash('message', 'This email is already registered as an employee');
            Session::flash('icon', 'error');
            return back()->withInput();
        }

        $this->EmployeeRep->Store([
            'business_id' => $business->id,
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'phone' => $request->phone,
        ]);

        Session::flash('message', 'Employee added successfully');
        Session::flash('icon', 'success');
        return redirect()->route('business.employees');
    }

    /**
     * Show edit form
     */
    public function Edit($id)
    {
        $business = Auth::guard('business')->user();
        $employee = $this->EmployeeRep->GetEmployee($id, $business->id);

        if (!$employee) {
            Session::flash('message', 'Employee not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.employees');
        }

        return view('business.employees.addedit', [
            'business' => $business,
            'data' => $employee,
        ]);
    }

    /**
     * Update employee
     */
    public function Update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $business = Auth::guard('business')->user();
        $employee = $this->EmployeeRep->GetEmployee($id, $business->id);

        if (!$employee) {
            Session::flash('message', 'Employee not found');
            Session::flash('icon', 'error');
            return redirect()->route('business.employees');
        }

        // Check if email already exists (for different employee)
        $existingEmployee = \App\Models\Employee::where('business_id', $business->id)
            ->where('email', strtolower($request->email))
            ->where('id', '!=', $id)
            ->first();

        if ($existingEmployee) {
            Session::flash('message', 'This email is already registered for another employee');
            Session::flash('icon', 'error');
            return back()->withInput();
        }

        $this->EmployeeRep->Update($id, $business->id, [
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'phone' => $request->phone,
        ]);

        Session::flash('message', 'Employee updated successfully');
        Session::flash('icon', 'success');
        return redirect()->route('business.employees');
    }

    /**
     * Delete employee
     */
    public function Delete($id)
    {
        $business = Auth::guard('business')->user();
        $result = $this->EmployeeRep->Delete($id, $business->id);

        if ($result) {
            Session::flash('message', 'Employee deleted successfully');
            Session::flash('icon', 'success');
        } else {
            Session::flash('message', 'Employee not found');
            Session::flash('icon', 'error');
        }

        return redirect()->route('business.employees');
    }

    /**
     * Show import form
     */
    public function ImportForm()
    {
        $business = Auth::guard('business')->user();
        return view('business.employees.import', [
            'business' => $business,
        ]);
    }

    /**
     * Process Excel/CSV import
     */
    public function Import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $business = Auth::guard('business')->user();

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header row
            $header = array_shift($rows);

            // Find column indices
            $nameIndex = array_search('name', array_map('strtolower', $header));
            $emailIndex = array_search('email', array_map('strtolower', $header));
            $departmentIndex = array_search('department', array_map('strtolower', $header));
            $phoneIndex = array_search('phone', array_map('strtolower', $header));

            if ($nameIndex === false || $emailIndex === false) {
                Session::flash('message', 'File must have "name" and "email" columns');
                Session::flash('icon', 'error');
                return back();
            }

            $employees = [];
            foreach ($rows as $row) {
                $employees[] = [
                    'name' => $row[$nameIndex] ?? '',
                    'email' => $row[$emailIndex] ?? '',
                    'department' => $departmentIndex !== false ? ($row[$departmentIndex] ?? '') : '',
                    'phone' => $phoneIndex !== false ? ($row[$phoneIndex] ?? '') : '',
                ];
            }

            $result = $this->EmployeeRep->BulkImport($business->id, $employees);

            Session::flash('message', "Import complete! {$result['imported']} imported, {$result['skipped']} skipped");
            Session::flash('icon', 'success');
            return redirect()->route('business.employees');
        } catch (\Exception $e) {
            Session::flash('message', 'Error processing file: ' . $e->getMessage());
            Session::flash('icon', 'error');
            return back();
        }
    }
}
