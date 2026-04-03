<?php

namespace App\Repositories\Admin;

use App\Models\ContactUs;

class ContactUsRepository
{
    /**
     * Get all contact requests ordered by latest first.
     */
    public function GetContacts()
    {
        return ContactUs::with('business')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get a single contact request by ID.
     */
    public function GetContact($id)
    {
        return ContactUs::with('repliedByUser')->find($id);
    }

    /**
     * Store a reply for a contact request.
     */
    public function StoreReply($id, $reply, $repliedBy)
    {
        $contact = ContactUs::find($id);
        if ($contact) {
            $contact->reply = $reply;
            $contact->replied_at = now();
            $contact->replied_by = $repliedBy;
            $contact->status = 'replied';
            $contact->save();
            return $contact;
        }
        return null;
    }

    /**
     * Delete a contact request.
     */
    public function DeleteContact($id)
    {
        return ContactUs::destroy($id);
    }
}
