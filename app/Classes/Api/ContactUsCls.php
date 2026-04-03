<?php

namespace App\Classes\Api;

use App\Models\ContactUs;
use Exception;
use App\General\General;

class ContactUsCls
{
    /**
     * Submit a new contact us request.
     */
    public function Submit($postData)
    {
        try {
            // Validate required fields
            if (empty($postData['name'])) {
                return General::setResponse('VALIDATION_ERROR', 'Name is required');
            }

            if (empty($postData['email'])) {
                return General::setResponse('VALIDATION_ERROR', 'Email is required');
            }

            if (!filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
                return General::setResponse('VALIDATION_ERROR', 'Invalid email format');
            }

            if (empty($postData['subject'])) {
                return General::setResponse('VALIDATION_ERROR', 'Subject is required');
            }

            if (empty($postData['message'])) {
                return General::setResponse('VALIDATION_ERROR', 'Message is required');
            }

            // Validate company_id if provided (maps to business_id)
            if (!empty($postData['company_id'])) {
                if (!\App\Models\Business::where('id', $postData['company_id'])->exists()) {
                    return General::setResponse('VALIDATION_ERROR', 'Invalid company ID');
                }
            }

            // Create contact request
            $contact = ContactUs::create([
                'name' => $postData['name'],
                'email' => $postData['email'],
                'subject' => $postData['subject'],
                'message' => $postData['message'],
                'status' => 'pending',
                'business_id' => $postData['company_id'] ?? null, // Map input company_id to business_id
            ]);

            $data = General::setResponse('SUCCESS', 'Your message has been submitted successfully. We will get back to you soon.');
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', 'Something went wrong. Please try again later.');
        }
    }
}
