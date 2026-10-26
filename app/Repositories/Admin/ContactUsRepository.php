<?php

namespace App\Repositories\Admin;

use App\Models\ContactUs;

class ContactUsRepository
{
    /**
     * Get all contact requests with optional filter (central vs specific business)
     */
    public function GetContacts($filter = null)
    {
        $query = ContactUs::with('business')->orderBy('created_at', 'desc');

        if ($filter === 'central') {
            $query->whereNull('business_id');
        } elseif (!empty($filter) && $filter !== 'all') {
            $query->where('business_id', (int) $filter);
        }

        return $query->get();
    }


    /**
     * Get a single contact request by ID.
     */
    public function GetContact($id)
    {
        return ContactUs::with(['repliedByUser', 'business'])->find($id);
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
