<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Repositories\Business\BusinessActivityLogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessActivityLogController extends Controller
{
    protected BusinessActivityLogRepository $logRepository;

    public function __construct(BusinessActivityLogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display activity logs strictly scoped to the authenticated bank / business
     */
    public function index(Request $request)
    {
        $businessId = Auth::guard('business')->id();
        $module = $request->query('module');

        $logs = $this->logRepository->getLogsByBusiness($businessId, $module, 20);
        $modules = $this->logRepository->getDistinctModules($businessId);

        return view('business.logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'selectedModule' => $module,
            'page_name' => __('messages.activity_logs') ?? 'Activity Logs',
        ]);
    }
}
