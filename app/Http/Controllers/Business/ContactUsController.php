<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Repositories\Business\ContactUsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    protected $ContactUsRep;

    public function __construct(ContactUsRepository $ContactUsRep)
    {
        $this->ContactUsRep = $ContactUsRep;
    }

    public function Index()
    {
        $data = $this->ContactUsRep->GetContacts();
        return view('business.contacts.manage', ['data' => $data, 'page_name' => 'Contact Us']);
    }

    public function ViewContact($id)
    {
        $data = $this->ContactUsRep->GetContact($id);
        if (!$data) {
            return redirect()->route('business.contacts')->with('error', 'Contact request not found.');
        }
        return view('business.contacts.view', ['data' => $data, 'page_name' => 'View Contact Request']);
    }

    public function Reply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // We are passing 0 or null as replied_by because of FK constraint to users table logic discussed in repository
        $this->ContactUsRep->ReplyContact($id, $request->reply, null);

        // Send email to the user with the reply content
        try {
            $contact = $this->ContactUsRep->GetContact($id);
            if ($contact && !empty($contact->email)) {
                $data = [
                    'name' => $contact->name,
                    'subject' => $contact->subject,
                    'original_message' => $contact->message,
                    'reply' => $request->reply,
                ];

                \Illuminate\Support\Facades\Mail::to($contact->email)->send(
                    new \App\Mail\DailyMail(
                        'Re: ' . $contact->subject . ' - ' . config('app.name'),
                        'emails.contact-reply',
                        $data
                    )
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send contact reply email from business: ' . $e->getMessage());
        }

        Session::flash('message', 'Reply sent successfully.');
        Session::flash('icon', 'success');

        return redirect()->route('business.contacts.view', $id);
    }

    public function Delete($id)
    {
        $this->ContactUsRep->DeleteContact($id);
        Session::flash('message', 'Contact request deleted successfully.');
        Session::flash('icon', 'success');
        return redirect()->route('business.contacts');
    }
}
