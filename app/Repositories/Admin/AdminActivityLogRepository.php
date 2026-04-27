<?php

namespace App\Repositories\Admin;

use App\Models\AdminActivityLog;
use App\Repositories\BaseRepository;

class AdminActivityLogRepository extends BaseRepository
{
    public function model()
    {
        return AdminActivityLog::class;
    }

    /**
     * Log an admin activity
     */
    public function logActivity($module, $action, $moduleId = null, $description = null, $data = null)
    {
        $adminId = auth()->check() ? auth()->id() : null;
        
        return $this->model->create([
            'admin_id' => $adminId,
            'module' => $module,
            'action' => $action,
            'module_id' => $moduleId,
            'description' => $description,
            'data' => $data,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Get latest logs
     */
    public function getLatestLogs($limit = 50)
    {
        return $this->model->with('admin')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
