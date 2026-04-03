<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Classes\Business\SectionCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    protected $SectionCls;

    public function __construct(SectionCls $SectionCls)
    {
        $this->SectionCls = $SectionCls;
    }

    /**
     * Get current business ID
     */
    private function getBusinessId()
    {
        return Auth::guard('business')->id();
    }

    /**
     * Display a listing of sections
     */
    public function Index()
    {
        $sections = $this->SectionCls->GetAllSections($this->getBusinessId());
        return view('business.sections.manage', compact('sections'));
    }

    /**
     * Show the form for creating a new section
     */
    public function Create()
    {
        $nextOrder = $this->SectionCls->GetNextOrder($this->getBusinessId());
        return view('business.sections.addedit', compact('nextOrder'));
    }

    /**
     * Show the form for editing a section
     */
    public function Edit($id)
    {
        $data = $this->SectionCls->GetSection($id, $this->getBusinessId());
        if (!$data) {
            return redirect()->route('business.sections');
        }
        return view('business.sections.addedit', compact('data'));
    }

    /**
     * Store a newly created or updated section
     */
    public function Store(Request $request)
    {
        $request->validate([
            // 'title_en' => 'required|string|max:255',
            // 'title_fr' => 'required|string|max:255',
        ]);

        $id = $request->input('id', 0);
        return $this->SectionCls->StoreSection($request, $this->getBusinessId(), $id);
    }

    /**
     * Remove the specified section
     */
    public function Delete($id)
    {
        return $this->SectionCls->DeleteSection($id, $this->getBusinessId());
    }

    /**
     * Update section order (AJAX)
     */
    public function UpdateOrder(Request $request)
    {
        $orderedIds = $request->input('order', []);
        $result = $this->SectionCls->UpdateOrder($orderedIds, $this->getBusinessId());
        return response()->json(['success' => $result]);
    }

    /**
     * Change section status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SectionCls->ChangeStatus($id, $status, $this->getBusinessId());
    }
}
