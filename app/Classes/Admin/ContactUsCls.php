<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Admin\ContactUsRepository;
use App\Mail\DailyMail;
use Exception;

class ContactUsCls
{
    protected $ContactUsRep;

    public function __construct(ContactUsRepository $ContactUsRep)
    {
        $this->ContactUsRep = $ContactUsRep;
    }

    /**
     * Get all contact requests.
     */
    public function GetContacts()
    {
        try {
            return $this->ContactUsRep->GetContacts();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single contact request.
     */
    public function GetContact($id)
    {
        try {
            return $this->ContactUsRep->GetContact($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reply to a contact request and send email.
     */
    public function ReplyToContact($id, $reply)
    {
        try {
            $contact = $this->ContactUsRep->GetContact($id);

            if (!$contact) {
                Session::flash('message', 'Contact request not found');
                Session::flash('icon', 'error');
                return redirect()->route('managecontacts');
            }

            // Store the reply
            $contact = $this->ContactUsRep->StoreReply($id, $reply, Auth::id());

            // Send email to the user
            $this->SendReplyEmail($contact, $reply);

            Session::flash('message', 'Reply sent successfully');
            Session::flash('icon', 'success');
            return redirect()->route('managecontacts');
        } catch (Exception $e) {
            Log::error('Failed to reply to contact: ' . $e->getMessage());
            Session::flash('message', 'Something went wrong');
            Session::flash('icon', 'error');
            return redirect()->route('managecontacts');
        }
    }

    /**
     * Send reply email to the contact.
     */
    protected function SendReplyEmail($contact, $reply)
    {
        try {
            $data = [
                'name' => $contact->name,
                'subject' => $contact->subject,
                'original_message' => $contact->message,
                'reply' => $reply,
            ];

            Mail::to($contact->email)->send(
                new DailyMail(
                    'Re: ' . $contact->subject . ' - ' . config('app.name'),
                    'emails.contact-reply',
                    $data
                )
            );

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send contact reply email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a contact request.
     */
    public function DeleteContact($id)
    {
        try {
            $this->ContactUsRep->DeleteContact($id);
            Session::flash('message', 'Contact deleted successfully');
            Session::flash('icon', 'success');
            return redirect()->route('managecontacts');
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }
}
