<?php
namespace App\Classes\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Repositories\Api\UserSubscriptionsRepository;
use App\Repositories\Api\PlansRepository;
use App\Repositories\Api\UserRepository;
use App\General\Validate;
use App\General\General;
use App\Models\InAppAuthToken;
use App\Models\Plans;
use Carbon\Carbon;
use Auth;
use Exception;
use Stripe;

class UserSubscriptionsCls {

    protected $UserSubscriptionsRep;
    protected $PlansRep;
    protected $UserRep;

    public function __construct(UserSubscriptionsRepository $UserSubscriptionsRep, PlansRepository $PlansRep, UserRepository $UserRep) {
        $this->UserSubscriptionsRep  = $UserSubscriptionsRep;
        $this->PlansRep         = $PlansRep;
        $this->UserRep          = $UserRep;
    }
    
    public function InitPayment($postData){

        try{
            $requiredValidate = Validate::required($postData, array('plan_id'));
            if ($requiredValidate->fails()) { return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first()); }
            
            $PlanObj = $this->PlansRep->GetPlan($postData['plan_id']);
            $UserObj = $this->UserRep->GetUser(Auth::user()->id);
            
            if(empty($PlanObj)){
                return General::setResponse('VALIDATION_ERROR', 'Plan not found.');
            }

            if(empty($UserObj)){
                return General::setResponse('VALIDATION_ERROR', 'User not found.');
            }

            $Stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
            
            if(empty($UserObj->stripe_customer_id)){

                $customer = $Stripe->customers->create([
                    'name'  => $UserObj->name, 
                    'email' => $UserObj->email
                ]);
                
                if(empty($customer->id)){
                    return General::setResponse('VALIDATION_ERROR', 'Customer not created.');
                }

                $UserObj->update(['stripe_customer_id'=>$customer->id]);
                $CustomerID = $customer->id;
            
            } else {
                $CustomerID = $UserObj->stripe_customer_id;
            }
            
            $Amount = ($PlanObj->price*100);

            $Price = $Stripe->prices->create([
                'unit_amount'   => $Amount,
                'currency'      => 'usd',
                'recurring'     => ['interval' => 'month', 'interval_count' => $PlanObj->validity],
                'product_data'  => ['name' => $PlanObj->name]
            ]);

            if(empty($Price->id)){
                return General::setResponse('VALIDATION_ERROR', 'payment gatway not created price.');
            }

            $ephemeralKey = $Stripe->ephemeralKeys->create(['customer' => $CustomerID], ['stripe_version' => '2022-08-01']);
            if(empty($ephemeralKey->secret)){
                return General::setResponse('VALIDATION_ERROR', 'Ephemeral not created.');
            }

            $SubscriptionRes  = $Stripe->subscriptions->create([
                'customer'          => $CustomerID,
                'items'             => [['price' => $Price->id]],
                'payment_behavior'  => 'default_incomplete',
                'expand'            => ['latest_invoice.payment_intent'],
            ]);

            if(empty($SubscriptionRes->id)){
                return General::setResponse('VALIDATION_ERROR', 'Subscription not created.');
            }

            // $paymentIntent  = $Stripe->paymentIntents->create([
            //     'amount'                => $Amount,
            //     'currency'              => 'usd',
            //     'customer'              => $CustomerID,
            //     'payment_method_types'  => ['card'],
            //     'confirmation_method'   => 'manual',
            //     //'confirm'  => true,
            // ]);

            // if(empty($paymentIntent->client_secret)){
            //     return General::setResponse('VALIDATION_ERROR', 'Something went wrong payment not initialized.');
            // }

            $response = array('payment_intent' => $SubscriptionRes->latest_invoice->payment_intent->client_secret, 'ephemeral_key' => $ephemeralKey->secret, 'customer_id' => $CustomerID, 'subscription_id' => $SubscriptionRes->id, 'public_key' => env('STRIPE_PUBLIC_KEY'));
            
            if($response){
                $data = General::setResponse('SUCCESS', "Payment initialized successfully.");
                $data['data'] = $response;
                return $data;
            } else {
                return General::setResponse('VALIDATION_ERROR', 'Something went wrong.');
            }
            
        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function CompletePayment($postData){
        try{
            $user = Auth::user();
            if (!$user) {
                return General::setResponse('UNAUTHORIZED', 'User not authenticated.');
            }

            // ANDROID IN-APP PURCHASE FLOW
            if(isset($postData['purchase_token']) && !empty($postData['purchase_token'])){

                $requiredValidate = Validate::required($postData, array('purchase_token'));
                if ($requiredValidate->fails()) { 
                    return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first()); 
                }

                $productId = $postData['product_id'] ?? ($postData['android_product_id'] ?? null);
                $startDate = null;
                $endDate = null;
                $status = 1;

                // Attempt Google Play validation if credentials & SUB_URL exist
                $google_auth_refresh_token = InAppAuthToken::where('identifier', 'google')->first();
                if($google_auth_refresh_token && !empty($google_auth_refresh_token->token_expiry_time) && date('Y-m-d H:i:s') > $google_auth_refresh_token->token_expiry_time){
                    General::getGoogleAuthRefreshToken();
                    $google_auth_refresh_token = InAppAuthToken::where('identifier', 'google')->first();
                }

                $subUrl = env('SUB_URL');
                if(!empty($subUrl) && $google_auth_refresh_token && !empty($google_auth_refresh_token['access_token'])){
                    $purchase_response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $google_auth_refresh_token['access_token'],
                        'Content-Type'  => 'application/json',
                    ])->get($subUrl . $postData['purchase_token']);
                    
                    if($purchase_response->successful()){
                        $InAppPurchaseRes = json_decode($purchase_response->body());
                        if ($InAppPurchaseRes && !empty($InAppPurchaseRes->lineItems[0])) {
                            $LineItems = $InAppPurchaseRes->lineItems[0];
                            if (!empty($InAppPurchaseRes->startTime)) {
                                $startDate = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->startTime));
                            }
                            if (!empty($LineItems->expiryTime)) {
                                $endDate = date('Y-m-d H:i:s', strtotime($LineItems->expiryTime));
                            }
                            $productId = $LineItems->productId ?? $productId;

                            if (isset($InAppPurchaseRes->subscriptionState) && $InAppPurchaseRes->subscriptionState == 'SUBSCRIPTION_STATE_ACTIVE'){
                                $status = 1;
                            } else {
                                $status = 0;
                            }
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('[Google In-App] API verification failed with status: ' . $purchase_response->status());
                    }
                }

                // Match Plan
                $planObj = null;
                if ($productId) {
                    $planObj = Plans::where('android_product_id', $productId)->first();
                }
                if (!$planObj && !empty($postData['plan_id'])) {
                    $planObj = Plans::find($postData['plan_id']);
                }

                if (!$planObj) {
                    return General::setResponse('VALIDATION_ERROR', "Plan not found for Android product ID: " . ($productId ?? 'unknown'));
                }

                // Ensure valid dates
                if (empty($startDate)) {
                    $startDate = Carbon::now()->format('Y-m-d H:i:s');
                }
                if (empty($endDate)) {
                    $startCarbon = Carbon::parse($startDate);
                    if ($planObj->validity_type === 'year') {
                        $endDate = $startCarbon->addYears($planObj->validity ?: 1)->format('Y-m-d H:i:s');
                    } else {
                        $endDate = $startCarbon->addMonths($planObj->validity ?: 1)->format('Y-m-d H:i:s');
                    }
                }

                $CurrentPlan    = $this->UserSubscriptionsRep->GetUserPlan();
                $CurrentPlanID  = (!empty($CurrentPlan)) ? $CurrentPlan->id : 0;

                // Fixed typo: use purchase_token instead of purchse_token
                $response = $this->UserSubscriptionsRep->AndroidAddEditUserSubscription(
                    $planObj->id, 
                    $postData['purchase_token'], 
                    $startDate, 
                    $endDate, 
                    $status, 
                    $CurrentPlanID
                );

                $this->UserRep->UpdateSubscription($planObj->id);

                $data = General::setResponse('SUCCESS', "Subscription completed successfully.");
                $data['data'] = [
                    'plan_id'                 => $planObj->id,
                    'plan_name'               => $planObj->name,
                    'subscription_start_date' => $startDate,
                    'subscription_end_date'   => $endDate,
                    'status'                  => $status,
                ];
                return $data;

            // APPLE iOS IN-APP PURCHASE FLOW
            } else {
              
                $requiredValidate = Validate::required($postData, array('receipt_id'));
                if ($requiredValidate->fails()) { 
                    return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first()); 
                }
                
                $liveUrl = env('LIVE_IN_APP_PURCHASE_VERIFY_RECEIPT_URL', 'https://buy.itunes.apple.com/verifyReceipt');
                $sandboxUrl = env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL', 'https://sandbox.itunes.apple.com/verifyReceipt');
                $verifyReceiptUrl = (env('APP_ENV') == 'production') ? $liveUrl : $sandboxUrl;
                
                $ReceiptRes = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post($verifyReceiptUrl, [
                        'password'      => env('IN_APP_PURCHASE_PASSWORD'), 
                        'receipt-data'  => $postData['receipt_id']
                    ]);
                
                if(!$ReceiptRes->successful()){
                    $data = General::setResponse('VALIDATION_ERROR', "Unable to communicate with App Store receipt verification service.");
                    $data['data'] = (object)[];
                    return $data;
                }
                
                $InAppPurchaseRes = json_decode($ReceiptRes->body());

                // Auto-retry against Apple Sandbox if Apple returns status 21007
                // (e.g. TestFlight or App Store sandbox testing against production URL)
                if (isset($InAppPurchaseRes->status) && $InAppPurchaseRes->status === 21007) {
                    $ReceiptRes = Http::withHeaders(['Content-Type' => 'application/json'])
                        ->post($sandboxUrl, [
                            'password'     => env('IN_APP_PURCHASE_PASSWORD'),
                            'receipt-data' => $postData['receipt_id']
                        ]);

                    if ($ReceiptRes->successful()) {
                        $InAppPurchaseRes = json_decode($ReceiptRes->body());
                    }
                }

                if(!isset($InAppPurchaseRes->status) || $InAppPurchaseRes->status !== 0){
                    $data = General::setResponse('VALIDATION_ERROR', "Apple receipt verification failed.");
                    $data['data'] = $InAppPurchaseRes ?? (object)[];
                    return $data;
                }

                $productId = null;
                $StartDate = null;
                $EndDate   = null;

                // Safely inspect latest_receipt_info (note: in PHP json_decode, this is an array, NOT an object!)
                if(!empty($InAppPurchaseRes->latest_receipt_info) && is_array($InAppPurchaseRes->latest_receipt_info)){
                    $latestItem = null;
                    $maxTime = 0;
                    foreach ($InAppPurchaseRes->latest_receipt_info as $item) {
                        $itemTime = isset($item->expires_date_ms) ? (int)$item->expires_date_ms : (isset($item->purchase_date_ms) ? (int)$item->purchase_date_ms : 0);
                        if ($itemTime >= $maxTime) {
                            $maxTime = $itemTime;
                            $latestItem = $item;
                        }
                    }
                    if (!$latestItem) {
                        $latestItem = end($InAppPurchaseRes->latest_receipt_info);
                    }

                    if ($latestItem) {
                        $productId = $latestItem->product_id ?? null;
                        if (!empty($latestItem->purchase_date_ms)) {
                            $StartDate = date('Y-m-d H:i:s', (int)($latestItem->purchase_date_ms / 1000));
                        } elseif (!empty($latestItem->purchase_date)) {
                            $StartDate = date('Y-m-d H:i:s', strtotime($latestItem->purchase_date));
                        }

                        if (!empty($latestItem->expires_date_ms)) {
                            $EndDate = date('Y-m-d H:i:s', (int)($latestItem->expires_date_ms / 1000));
                        } elseif (!empty($latestItem->expires_date)) {
                            $EndDate = date('Y-m-d H:i:s', strtotime($latestItem->expires_date));
                        }
                    }
                }

                // Fallback to in_app array if latest_receipt_info is absent (e.g. consumable / non-renewing)
                if (empty($productId)) {
                    $inAppList = $InAppPurchaseRes->receipt->in_app ?? ($InAppPurchaseRes->in_app ?? []);
                    if (!empty($inAppList) && is_array($inAppList)) {
                        $lastItem = end($inAppList);
                        $productId = $lastItem->product_id ?? null;
                        if (!empty($lastItem->purchase_date_ms)) {
                            $StartDate = date('Y-m-d H:i:s', (int)($lastItem->purchase_date_ms / 1000));
                        } elseif (!empty($lastItem->purchase_date)) {
                            $StartDate = date('Y-m-d H:i:s', strtotime($lastItem->purchase_date));
                        }
                    }
                }

                // Fallback to postData product ID / plan ID if provided
                if (empty($productId)) {
                    $productId = $postData['inApp_product_id'] ?? ($postData['product_id'] ?? ($postData['ios_product_id'] ?? null));
                }

                // Match Plan
                $PlanObj = null;
                if ($productId) {
                    $PlanObj = Plans::where('ios_product_id', $productId)->first();
                }
                if (!$PlanObj && !empty($postData['plan_id'])) {
                    $PlanObj = Plans::find($postData['plan_id']);
                }

                if (!$PlanObj) {
                    return General::setResponse('VALIDATION_ERROR', "Plan not found for iOS product ID: " . ($productId ?? 'unknown'));
                }

                // Ensure valid dates
                if (empty($StartDate)) {
                    $StartDate = Carbon::now()->format('Y-m-d H:i:s');
                }
                if (empty($EndDate)) {
                    $startCarbon = Carbon::parse($StartDate);
                    if ($PlanObj->validity_type === 'year') {
                        $EndDate = $startCarbon->addYears($PlanObj->validity ?: 1)->format('Y-m-d H:i:s');
                    } else {
                        $EndDate = $startCarbon->addMonths($PlanObj->validity ?: 1)->format('Y-m-d H:i:s');
                    }
                }

                $CurrentPlan    = $this->UserSubscriptionsRep->GetUserPlan();
                $CurrentPlanID  = (!empty($CurrentPlan)) ? $CurrentPlan->id : 0;

                $response = $this->UserSubscriptionsRep->AddEditUserSubscription(
                    $PlanObj->id, 
                    $postData['receipt_id'], 
                    $StartDate, 
                    $EndDate, 
                    $CurrentPlanID, 
                    $ReceiptRes->body()
                );

                $this->UserRep->UpdateSubscription($PlanObj->id);

                $data = General::setResponse('SUCCESS', "Subscription completed successfully.");
                $data['data'] = [
                    'plan_id'                 => $PlanObj->id,
                    'plan_name'               => $PlanObj->name,
                    'subscription_start_date' => $StartDate,
                    'subscription_end_date'   => $EndDate,
                    'status'                  => 1,
                ];
                return $data;

            }

        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetUserCurrentPlan($postData)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return General::setResponse('UNAUTHORIZED', 'User not authenticated.');
            }

            $availablePlans = $this->PlansRep->GetPlans();

            // 1. Check if user is associated with a business (joined under business or business user)
            if (!empty($user->business_id)) {
                $business = \App\Models\Business::with('plan')->find($user->business_id);

                if (!$business) {
                    $planData = [
                        'user_id'           => $user->id,
                        'user_name'         => $user->name,
                        'user_email'        => $user->email,
                        'is_subscribed'     => false,
                        'subscription_type' => 'business',
                        'status'            => 0,
                        'status_text'       => 'Business Not Found',
                        'is_business_user'  => true,
                        'business_id'       => $user->business_id,
                    ];
                    $data = General::setResponse('SUCCESS', 'No subscription plan found.');
                    $data['data'] = $this->formatCurrentPlanResponse($planData);
                    $data['plans'] = $availablePlans;
                    return $data;
                }

                $now = Carbon::now();
                $startDate = $business->subscription_start_date ? Carbon::parse($business->subscription_start_date) : null;
                $endDate = $business->subscription_end_date ? Carbon::parse($business->subscription_end_date)->endOfDay() : null;

                $isActive = false;
                $statusText = 'No Plan Assigned';

                if ($business->status == 1 && $startDate && $endDate) {
                    if ($now->between($startDate, $endDate)) {
                        $isActive = true;
                        $statusText = 'Active';
                    } elseif ($now->gt($endDate)) {
                        $statusText = 'Expired';
                    } else {
                        $statusText = 'Upcoming';
                    }
                }

                $daysRemaining = ($isActive && $endDate) ? max(0, (int)$now->diffInDays($endDate, false)) : 0;
                $planObj = $business->plan;

                $planData = [
                    'id'                      => $business->id,
                    'user_id'                 => $user->id,
                    'user_name'               => $user->name,
                    'user_email'              => $user->email,
                    'is_subscribed'           => $isActive,
                    'subscription_type'       => 'business',
                    'status'                  => $isActive ? 1 : 0,
                    'status_text'             => $statusText,
                    'plan_id'                 => $business->plan_id,
                    'plan_name'               => $planObj ? $planObj->name : 'Business Cash Plan',
                    'price'                   => $planObj ? (string)$planObj->price : '0.00',
                    'validity'                => $planObj ? $planObj->validity : null,
                    'validity_type'           => $planObj ? $planObj->validity_type : 'month',
                    'subscription_start_date' => $business->subscription_start_date ? Carbon::parse($business->subscription_start_date)->format('Y-m-d H:i:s') : null,
                    'subscription_end_date'   => $business->subscription_end_date ? Carbon::parse($business->subscription_end_date)->format('Y-m-d H:i:s') : null,
                    'days_remaining'          => $daysRemaining,
                    'purchase_from'           => $business->payment_mode ?? 'cash',
                    'receipt_id'              => null,
                    'purchase_token'          => null,
                    'ios_product_id'          => $planObj ? $planObj->ios_product_id : null,
                    'android_product_id'      => $planObj ? $planObj->android_product_id : null,
                    'is_business_user'        => true,
                    'business_id'             => $business->id,
                    'business_name'           => $business->name,
                    'business_code'           => $business->business_code,
                    'user_quota'              => (int)($business->user_quota ?? 0),
                    'used_quota'              => (int)$business->getUsedQuota(),
                ];

                $data = General::setResponse('SUCCESS', 'Success.');
                $data['data'] = $this->formatCurrentPlanResponse($planData);
                $data['plans'] = $availablePlans;
                return $data;
            }

            // 2. Individual User (not associated with any business)
            $userSubPlan = $this->UserSubscriptionsRep->GetUserPlan($user->id);

            if (empty($userSubPlan) || empty($userSubPlan->subscription_end_date)) {
                $planData = [
                    'user_id'           => $user->id,
                    'user_name'         => $user->name,
                    'user_email'        => $user->email,
                    'is_subscribed'     => false,
                    'subscription_type' => 'individual',
                    'status'            => 0,
                    'status_text'       => 'No Active Plan',
                    'is_business_user'  => false,
                ];
                $data = General::setResponse('SUCCESS', 'No subscription plan found.');
                $data['data'] = $this->formatCurrentPlanResponse($planData);
                $data['plans'] = $availablePlans;
                return $data;
            }

            // Optional refresh for Apple iOS in-app receipts
            if (($userSubPlan->purchase_from === '0' || $userSubPlan->purchase_from === 0 || $userSubPlan->purchase_from === 'ios') && !empty($userSubPlan->receipt_id)) {
                try {
                    $VerifyReceiptUrl = (env('APP_ENV') == 'production') ? env('LIVE_IN_APP_PURCHASE_VERIFY_RECEIPT_URL', env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL')) : env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL');
                    if ($VerifyReceiptUrl && env('IN_APP_PURCHASE_PASSWORD')) {
                        $ReceiptRes = Http::timeout(3)->withHeaders(['Content-Type' => 'application/json'])
                            ->post($VerifyReceiptUrl, [
                                'password'     => env('IN_APP_PURCHASE_PASSWORD'),
                                'receipt-data' => $userSubPlan->receipt_id
                            ]);
                        if ($ReceiptRes->successful()) {
                            $InAppPurchaseRes = json_decode($ReceiptRes->body());
                            if (isset($InAppPurchaseRes->status) && $InAppPurchaseRes->status === 21007) {
                                $sandboxUrl = env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL', 'https://sandbox.itunes.apple.com/verifyReceipt');
                                $ReceiptRes = Http::timeout(3)->withHeaders(['Content-Type' => 'application/json'])
                                    ->post($sandboxUrl, [
                                        'password'     => env('IN_APP_PURCHASE_PASSWORD'),
                                        'receipt-data' => $userSubPlan->receipt_id
                                    ]);
                                if ($ReceiptRes->successful()) {
                                    $InAppPurchaseRes = json_decode($ReceiptRes->body());
                                }
                            }
                            if ($InAppPurchaseRes && isset($InAppPurchaseRes->status) && $InAppPurchaseRes->status === 0 && !empty($InAppPurchaseRes->latest_receipt_info) && is_array($InAppPurchaseRes->latest_receipt_info)) {
                                $latest = end($InAppPurchaseRes->latest_receipt_info);
                                if (!empty($latest->purchase_date)) {
                                    $userSubPlan->subscription_start_date = date('Y-m-d H:i:s', strtotime($latest->purchase_date));
                                }
                                if (!empty($latest->expires_date)) {
                                    $userSubPlan->subscription_end_date = date('Y-m-d H:i:s', strtotime($latest->expires_date));
                                }
                                $userSubPlan->save();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('In-app iOS receipt verification check failed: ' . $e->getMessage());
                }
            }
            // Optional refresh for Google Android in-app receipts
            elseif (($userSubPlan->purchase_from === '1' || $userSubPlan->purchase_from === 1 || $userSubPlan->purchase_from === 'android') && !empty($userSubPlan->purchase_token) && env('SUB_URL')) {
                try {
                    $googleAuth = InAppAuthToken::where('identifier', 'google')->first();
                    if ($googleAuth) {
                        if (date('Y-m-d H:i:s') > $googleAuth->token_expiry_time) {
                            General::getGoogleAuthRefreshToken();
                            $googleAuth = InAppAuthToken::where('identifier', 'google')->first();
                        }
                        $purchaseResponse = Http::timeout(3)->withHeaders([
                            'Authorization' => 'Bearer ' . $googleAuth->access_token,
                            'Content-Type'  => 'application/json',
                        ])->get(env('SUB_URL') . $userSubPlan->purchase_token);

                        if ($purchaseResponse->successful()) {
                            $inAppRes = json_decode($purchaseResponse->body());
                            if ($inAppRes && isset($inAppRes->lineItems[0])) {
                                $lineItem = $inAppRes->lineItems[0];
                                $userSubPlan->subscription_start_date = date('Y-m-d H:i:s', strtotime($inAppRes->startTime));
                                $userSubPlan->subscription_end_date = date('Y-m-d H:i:s', strtotime($lineItem->expiryTime));
                                $userSubPlan->status = ($userSubPlan->subscription_end_date > date('Y-m-d H:i:s')) ? 1 : 0;
                                $userSubPlan->save();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('In-app Android receipt verification check failed: ' . $e->getMessage());
                }
            }

            $now = Carbon::now();
            $startDate = $userSubPlan->subscription_start_date ? Carbon::parse($userSubPlan->subscription_start_date) : null;
            $endDate = $userSubPlan->subscription_end_date ? Carbon::parse($userSubPlan->subscription_end_date)->endOfDay() : null;

            $isActive = false;
            $statusText = 'Expired';

            if ($userSubPlan->status == 1 && $endDate && $now->lte($endDate)) {
                $isActive = true;
                $statusText = 'Active';
            }

            $daysRemaining = ($isActive && $endDate) ? max(0, (int)$now->diffInDays($endDate, false)) : 0;
            $planObj = $userSubPlan->plan ?? Plans::find($userSubPlan->plan_id);

            $planData = [
                'id'                      => $userSubPlan->id,
                'user_id'                 => $user->id,
                'user_name'               => $user->name,
                'user_email'              => $user->email,
                'is_subscribed'           => $isActive,
                'subscription_type'       => 'individual',
                'status'                  => $isActive ? 1 : 0,
                'status_text'             => $statusText,
                'plan_id'                 => $userSubPlan->plan_id,
                'plan_name'               => $planObj ? $planObj->name : 'Individual Plan',
                'price'                   => $planObj ? (string)$planObj->price : '0.00',
                'validity'                => $planObj ? $planObj->validity : null,
                'validity_type'           => $planObj ? $planObj->validity_type : 'month',
                'subscription_start_date' => $userSubPlan->subscription_start_date ? Carbon::parse($userSubPlan->subscription_start_date)->format('Y-m-d H:i:s') : null,
                'subscription_end_date'   => $userSubPlan->subscription_end_date ? Carbon::parse($userSubPlan->subscription_end_date)->format('Y-m-d H:i:s') : null,
                'days_remaining'          => $daysRemaining,
                'purchase_from'           => $this->mapPurchaseFrom($userSubPlan->purchase_from),
                'receipt_id'              => $userSubPlan->receipt_id,
                'purchase_token'          => $userSubPlan->purchase_token,
                'ios_product_id'          => $planObj ? $planObj->ios_product_id : null,
                'android_product_id'      => $planObj ? $planObj->android_product_id : null,
                'is_business_user'        => false,
                'business_id'             => null,
                'business_name'           => null,
                'business_code'           => null,
                'user_quota'              => null,
                'used_quota'              => null,
            ];

            $data = General::setResponse('SUCCESS', 'Success.');
            $data['data'] = $this->formatCurrentPlanResponse($planData);
            $data['plans'] = $availablePlans;
            return $data;

        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Format current plan response so both individual and business users receive the exact same JSON schema
     */
    public function formatCurrentPlanResponse($data = [])
    {
        return [
            'id'                      => $data['id'] ?? null,
            'user_id'                 => $data['user_id'] ?? null,
            'user_name'               => $data['user_name'] ?? null,
            'user_email'              => $data['user_email'] ?? null,
            'is_subscribed'           => (bool)($data['is_subscribed'] ?? false),
            'subscription_type'       => $data['subscription_type'] ?? 'individual',
            'status'                  => isset($data['status']) ? (int)$data['status'] : 0,
            'status_text'             => $data['status_text'] ?? 'No Active Plan',
            'plan_id'                 => $data['plan_id'] ?? null,
            'plan_name'               => $data['plan_name'] ?? null,
            'price'                   => isset($data['price']) ? (string)$data['price'] : null,
            'validity'                => $data['validity'] ?? null,
            'validity_type'           => $data['validity_type'] ?? 'month',
            'subscription_start_date' => $data['subscription_start_date'] ?? null,
            'subscription_end_date'   => $data['subscription_end_date'] ?? null,
            'days_remaining'          => isset($data['days_remaining']) ? (int)$data['days_remaining'] : 0,
            'purchase_from'           => $data['purchase_from'] ?? null,
            'receipt_id'              => $data['receipt_id'] ?? null,
            'purchase_token'          => $data['purchase_token'] ?? null,
            'ios_product_id'          => $data['ios_product_id'] ?? null,
            'android_product_id'      => $data['android_product_id'] ?? null,

            // Business metadata (null for individual users)
            'is_business_user'        => (bool)($data['is_business_user'] ?? false),
            'business_id'             => $data['business_id'] ?? null,
            'business_name'           => $data['business_name'] ?? null,
            'business_code'           => $data['business_code'] ?? null,
            'user_quota'              => isset($data['user_quota']) ? (int)$data['user_quota'] : null,
            'used_quota'              => isset($data['used_quota']) ? (int)$data['used_quota'] : null,
        ];
    }

    /**
     * Map purchase_from value to human-readable string
     */
    protected function mapPurchaseFrom($val): string
    {
        if ($val === 0 || $val === '0' || $val === 'ios') return 'ios';
        if ($val === 1 || $val === '1' || $val === 'android') return 'android';
        if ($val === 'cash' || $val === 2 || $val === '2') return 'cash';
        if ($val === 'stripe') return 'stripe';
        return (string)($val ?? 'cash');
    }
}
