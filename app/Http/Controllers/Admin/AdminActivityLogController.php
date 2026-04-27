<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\AdminActivityLogRepository;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    protected $LogRep;

    public function __construct(AdminActivityLogRepository $LogRep)
    {
        $this->LogRep = $LogRep;
    }

    public function index(Request $request)
    {
        $query = \App\Models\AdminActivityLog::with('admin')
            ->orderBy('created_at', 'desc');

        if ($request->has('module') && $request->module != '') {
            $query->where('module', $request->module);
        }

        if ($request->has('admin_id') && $request->admin_id != '') {
            $query->where('admin_id', $request->admin_id);
        }

        $logs = $query->paginate(20);
        $modules = \App\Models\AdminActivityLog::select('module')->distinct()->pluck('module');
        $admins = \App\Models\User::where('user_type', 1)->get();

        return view('logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'admins' => $admins,
            'page_name' => 'Activity Logs'
        ]);
    }
}
