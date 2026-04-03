<?php

namespace App\Http\Controllers\Admin;

use App\Classes\Admin\ContactUsCls;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    protected $ContactUsCls;

    public function __construct(ContactUsCls $ContactUsCls)
    {
        $this->ContactUsCls = $ContactUsCls;
    }

    /**
     * Display list of all contact requests.
     */
    public function ManageContacts()
    {
        $contacts = $this->ContactUsCls->GetContacts();
        return view('contacts.manage', ['contacts' => $contacts, 'page_name' => 'Contact Us']);
    }

    /**
     * View a single contact request.
     */
    public function ViewContact($id)
    {
        $contact = $this->ContactUsCls->GetContact($id);
        if (!$contact) {
            return redirect()->route('managecontacts');
        }
        return view('contacts.view', ['contact' => $contact, 'page_name' => 'View Contact']);
    }

    /**
     * Send reply to a contact request.
     */
    public function ReplyContact(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:contact_us,id',
            'reply' => 'required|string|min:10',
        ]);

        return $this->ContactUsCls->ReplyToContact($request->id, $request->reply);
    }

    /**
     * Delete a contact request.
     */
    public function DeleteContact($id)
    {
        return $this->ContactUsCls->DeleteContact($id);
    }
}
