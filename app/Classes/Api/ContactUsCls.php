<?php

namespace App\Classes\Api;

use App\Models\ContactUs;
use Exception;
use App\General\General;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactUsNotification;

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

            // Resolve business_id from multiple possible sources (explicit param, header, or authenticated user)
            $businessId = null;

            if (!empty($postData['business_id'])) {
                $businessId = (int) $postData['business_id'];
            } elseif (!empty($postData['company_id'])) {
                $businessId = (int) $postData['company_id'];
            }

            if (!$businessId) {
                $businessCode = request()->header('business_code')
                    ?? request()->header('business-code')
                    ?? ($postData['business_code'] ?? null);

                if (!empty($businessCode)) {
                    $foundByCode = \App\Models\Business::where('business_code', $businessCode)->first();
                    if ($foundByCode) {
                        $businessId = $foundByCode->id;
                    }
                }
            }

            if (!$businessId && auth()->check()) {
                $businessId = auth()->user()->business_id ?? null;
            }

            // Validate business if provided
            $business = null;
            if (!empty($businessId)) {
                $business = \App\Models\Business::find($businessId);
                if (!$business) {
                    return General::setResponse('VALIDATION_ERROR', 'Invalid bank or business ID');
                }
            }

            // Create contact request scoped to business (or null for central support)
            $contact = ContactUs::create([
                'name' => $postData['name'],
                'email' => $postData['email'],
                'subject' => $postData['subject'],
                'message' => $postData['message'],
                'status' => 'pending',
                'business_id' => $business ? $business->id : null,
            ]);

            // Smart Email Routing (PPT Slide 4: Central support routing vs Bank-specific contact)
            try {
                $centralSupportEmail = config('mail.support_address', 'support@negomaster.ch');

                if ($business) {
                    // Bank-specific routing
                    $bankContactEmail = !empty($business->contact_email) ? $business->contact_email : $business->email;
                    $recipientEmail = $bankContactEmail ?: $centralSupportEmail;

                    $notificationData = array_merge($postData, [
                        'business_name' => $business->name,
                        'business_code' => $business->business_code,
                        'routing_type' => 'bank_specific',
                    ]);

                    $mail = Mail::to($recipientEmail);
                    if ($recipientEmail !== $centralSupportEmail) {
                        $mail->bcc($centralSupportEmail);
                    }
                    $mail->send(new ContactUsNotification($notificationData));
                } else {
                    // Central support routing
                    $notificationData = array_merge($postData, [
                        'routing_type' => 'central_support',
                    ]);
                    Mail::to($centralSupportEmail)->send(new ContactUsNotification($notificationData));
                }
            } catch (Exception $mailException) {
                // Log the error but don't fail the request since the contact is already saved
                \Illuminate\Support\Facades\Log::error('Failed to send contact us email: ' . $mailException->getMessage());
            }

            $data = General::setResponse('SUCCESS', 'Your message has been submitted successfully. We will get back to you soon.');
            return $data;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', 'Something went wrong. Please try again later.');
        }
    }
}

