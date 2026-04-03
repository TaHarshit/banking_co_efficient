<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Admin\SectionCls;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    protected $SectionCls;

    public function __construct(SectionCls $SectionCls)
    {
        $this->SectionCls = $SectionCls;
    }

    /**
     * Display a listing of sections
     */
    public function ManageSections()
    {
        $sections = $this->SectionCls->GetAllSections();
        return view('sections.manage', compact('sections'));
    }

    /**
     * Show the form for creating a new section
     */
    public function CreateSection()
    {
        $nextOrder = $this->SectionCls->GetNextOrder();
        return view('sections.addedit', compact('nextOrder'));
    }

    /**
     * Show the form for editing a section
     */
    public function UpdateSection($id)
    {
        $data = $this->SectionCls->GetSection($id);
        if (!$data) {
            return redirect()->route('managesections');
        }
        return view('sections.addedit', compact('data'));
    }

    /**
     * Store a newly created or updated section
     */
    public function StoreSection(Request $request)
    {
        $request->validate([
            'title_en' => 'required|string|max:255',
            'title_fr' => 'required|string|max:255',
        ]);

        $id = $request->input('id', 0);
        return $this->SectionCls->StoreSection($request, $id);
    }

    /**
     * Remove the specified section
     */
    public function DeleteSection($id)
    {
        return $this->SectionCls->DeleteSection($id);
    }

    /**
     * Update section order (AJAX)
     */
    public function UpdateOrder(Request $request)
    {
        $orderedIds = $request->input('order', []);
        $result = $this->SectionCls->UpdateOrder($orderedIds);
        return response()->json(['success' => $result]);
    }

    /**
     * Change section status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SectionCls->ChangeStatus($id, $status);
    }
}
