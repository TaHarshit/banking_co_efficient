<?php

namespace App\Repositories\Business;

use App\Models\BusinessActivityLog;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BusinessActivityLogRepository extends BaseRepository
{
    public function model(): string
    {
        return BusinessActivityLog::class;
    }

    /**
     * Get paginated logs for a specific business with optional module filter
     */
    public function getLogsByBusiness(int $businessId, ?string $module = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model
            ->where('business_id', $businessId)
            ->orderBy('created_at', 'desc');

        if (!empty($module)) {
            $query->where('module', $module);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get distinct modules logged for a specific business
     */
    public function getDistinctModules(int $businessId): Collection
    {
        return $this->model
            ->where('business_id', $businessId)
            ->distinct()
            ->pluck('module');
    }

    /**
     * Log an activity for a business
     */
    public function logActivity(
        int $businessId,
        string $module,
        string $action,
        ?int $moduleId = null,
        ?string $description = null,
        mixed $data = null,
        ?int $actorId = null,
        ?string $actorName = null
    ): BusinessActivityLog {
        return $this->model->create([
            'business_id' => $businessId,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'module' => $module,
            'action' => $action,
            'module_id' => $moduleId,
            'description' => $description,
            'data' => $data,
            'ip_address' => request()->ip(),
        ]);
    }
}
