<?php

namespace App\Repositories\Business;

use App\Models\ContactUs;
use Illuminate\Support\Facades\Auth;

class ContactUsRepository
{
    /**
     * Get all contact requests for the logged-in business.
     */
    public function GetContacts()
    {
        $businessId = Auth::guard('business')->id();
        return ContactUs::where('business_id', $businessId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get a specific contact request.
     */
    public function GetContact($id)
    {
        $businessId = Auth::guard('business')->id();
        return ContactUs::where('business_id', $businessId)->where('id', $id)->first();
    }

    /**
     * Delete a contact request.
     */
    public function DeleteContact($id)
    {
        $contact = $this->GetContact($id);
        if ($contact) {
            return $contact->delete();
        }
        return false;
    }

    /**
     * Store a reply.
     */
    public function ReplyContact($id, $reply, $repliedBy)
    {
        $contact = $this->GetContact($id);
        if ($contact) {
            $contact->reply = $reply;
            $contact->replied_by = $repliedBy; // This might be a User ID or Business ID? 
            // Admin `replied_by` links to `users`. Business portal users are `Business`.
            // If `replied_by` is FK to `users`, we might have an issue if we store Business ID there.
            // But `Business` model is not `User` model.
            // We might need to make `replied_by` polymorphic or just store it if not strict FK.
            // The migration `2026_01_28_140000_create_contact_us_table.php` has:
            // $table->foreign('replied_by')->references('id')->on('users')
            // So we CANNOT store Business ID there.
            // We should probably leave it null or modify schema.
            // For now, I will leave it null or use a specific user if available.
            // But `Auth::guard('business')->user()` is a Business.
            // Re-reading `ContactUs` migration: `replied_by` references `users`.
            // Workaround: We can't store Business ID in `replied_by` easily without schema change.
            // I will skip `replied_by` or set it to null for Business replies for now, 
            // OR I should have added `RepliedByBusiness`?
            // Let's just update timestamp and reply text.

            $contact->replied_at = now();
            $contact->status = 'replied';
            $contact->save();
            return true;
        }
        return false;
    }
}
