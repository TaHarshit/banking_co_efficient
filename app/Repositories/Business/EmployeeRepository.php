<?php

namespace App\Repositories\Business;

use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

class EmployeeRepository
{
    /**
     * Get all employees for a business
     */
    public function GetEmployees($businessId)
    {
        return Employee::where('business_id', $businessId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get single employee
     */
    public function GetEmployee($id, $businessId)
    {
        return Employee::where('id', $id)
            ->where('business_id', $businessId)
            ->first();
    }

    /**
     * Store new employee
     */
    public function Store($data)
    {
        $data['email'] = strtolower($data['email']);
        return Employee::create($data);
    }

    /**
     * Update employee
     */
    public function Update($id, $businessId, $data)
    {
        $employee = $this->GetEmployee($id, $businessId);
        if ($employee) {
            $data['email'] = strtolower($data['email']);
            $employee->update($data);
            return $employee;
        }
        return null;
    }

    /**
     * Delete employee
     */
    public function Delete($id, $businessId)
    {
        $employee = $this->GetEmployee($id, $businessId);
        if ($employee) {
            return $employee->delete();
        }
        return false;
    }

    /**
     * Bulk import employees from array
     */
    public function BulkImport($businessId, $employees)
    {
        $imported = 0;
        $skipped = 0;

        foreach ($employees as $emp) {
            $email = strtolower(trim($emp['email'] ?? ''));
            $name = trim($emp['name'] ?? '');

            if (empty($email) || empty($name)) {
                $skipped++;
                continue;
            }

            // Check if already exists
            $exists = Employee::where('business_id', $businessId)
                ->where('email', $email)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Employee::create([
                'business_id' => $businessId,
                'name' => $name,
                'email' => $email,
                'department' => trim($emp['department'] ?? null),
                'phone' => trim($emp['phone'] ?? null),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Check if email is employee of business
     */
    public function IsEmployee($businessId, $email)
    {
        return Employee::where('business_id', $businessId)
            ->where('email', strtolower($email))
            ->exists();
    }
}
