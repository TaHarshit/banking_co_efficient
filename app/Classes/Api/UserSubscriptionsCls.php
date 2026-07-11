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
            if(isset($postData['purchase_token'])){

                $requiredValidate = Validate::required($postData, array('purchase_token'));
                if ($requiredValidate->fails()) { return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first()); }

                $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();

                if(date('Y-m-d H:i:s') > $google_auth_refresh_token->token_expiry_time){
                    $api = General::getGoogleAuthRefreshToken();
                    $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();
                }

                $purchase_response = Http::withHeaders([
                    'Authorization'     => 'Bearer' . ' ' .  $google_auth_refresh_token['access_token'],
                    'Content-Type' => 'application/json',
                ])->get(env('SUB_URL').$postData['purchase_token']);
                
                if($purchase_response->status()!=200){
                    $message ="Somthing went wrong";
                    $data = General::setResponse('VALIDATION_ERROR', $message);
                    $data['data'] = (object)[];
                    return $data;
                }
                
                $InAppPurchaseRes = json_decode($purchase_response->body());

                $LineItems = $InAppPurchaseRes->lineItems[0];

                $StartDate = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->startTime));
                $EndDate = date('Y-m-d H:i:s', strtotime($LineItems->expiryTime));
                $Product_id = $LineItems->productId;

                if ($InAppPurchaseRes->subscriptionState == 'SUBSCRIPTION_STATE_ACTIVE'){
                    $status = 1;
                }else{
                    $status = 0;
                }

                $plan_id = Plans::where('android_product_id', $Product_id)->first()->id;

                $CurrentPlan    = $this->UserSubscriptionsRep->GetUserPlan();
                $CurrentPlanID  = (!empty($CurrentPlan)) ? $CurrentPlan->id : 0;

                $response = $this->UserSubscriptionsRep->AndroidAddEditUserSubscription($plan_id, $postData['purchse_token'], $StartDate, $EndDate, $status, $CurrentPlanID);

                $updateSub = $this->UserRep->UpdateSubscription($plan_id);

                $data = General::setResponse('SUCCESS', "Success.");
                $data['data'] = (object)[];
                return $data;

            }else{
              
                $requiredValidate = Validate::required($postData, array('receipt_id'));
                if ($requiredValidate->fails()) { return General::setResponse('VALIDATION_ERROR', $requiredValidate->errors()->first()); }
                
                $VerifyReceiptUrl = (env('APP_ENV')=='production') ? env('LIVE_IN_APP_PURCHASE_VERIFY_RECEIPT_URL') : env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL');
                
                $ReceiptRes = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post($VerifyReceiptUrl, [
                        'password'      => env('IN_APP_PURCHASE_PASSWORD'), 
                        'receipt-data'  => $postData['receipt_id']
                    ]);
                
                if($ReceiptRes->status()!=200){
                    $data = General::setResponse('VALIDATION_ERROR', "Somthing went wrong");
                    $data['data'] = (object)[];
                    return $data;
                }
                
                $InAppPurchaseRes = json_decode($ReceiptRes->body());

                if($InAppPurchaseRes->status!==0){
                    $data = General::setResponse('VALIDATION_ERROR', "Somthing went wrong with in app purchase.");
                    $data['data'] = json_decode($ReceiptRes->body());
                    return $data;
                }

                $CurrentPlan    = $this->UserSubscriptionsRep->GetUserPlan();
                
                if($InAppPurchaseRes->latest_receipt_info == null || $InAppPurchaseRes->latest_receipt_info->count() == 0){
                $PlanObj = Plans::where('ios_product_id',$InAppPurchaseRes->in_app[0]->product_id)->first();
                    
                $StartDate      = null;
                $EndDate        = null;
                } else{
                    
                $PlanObj = Plans::where('ios_product_id',$InAppPurchaseRes->latest_receipt_info[0]->product_id)->first();
                $StartDate      = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->latest_receipt_info[0]->purchase_date));
                $EndDate        = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->latest_receipt_info[0]->expires_date));
                }
                
                $CurrentPlanID  = (!empty($CurrentPlan)) ? $CurrentPlan->id : 0;

                
                $response = $this->UserSubscriptionsRep->AddEditUserSubscription($PlanObj->id, $postData['receipt_id'], $StartDate, $EndDate, $CurrentPlanID, $ReceiptRes->body());
                if (isset($postData['inApp_product_id'])){
                $updateSub = $this->UserRep->UpdateSubscription($postData['inApp_product_id']);    
                }else{
                $updateSub = $this->UserRep->UpdateSubscription($PlanObj->id);
                }
                $data = General::setResponse('SUCCESS', "Success.");
                $data['data'] = (object)[];
                return $data;

            }

        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetUserCurrentPlan($postData){
        try{

            $VerifyReceiptUrl = (env('APP_ENV')=='production') ? env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL') : env('SANBOX_IN_APP_PURCHASE_VERIFY_RECEIPT_URL');
            $UserSubPlan = $this->UserSubscriptionsRep->GetUserPlan();

            if(empty($UserSubPlan) || $UserSubPlan->subscription_end_date == null){
                $data = General::setResponse('SUCCESS', "No subscription plan found.");
                $data['data'] = (object)[];
                $data['plans'] = $this->PlansRep->GetPlans();
                return $data;
            }

            if($UserSubPlan->purchase_token == null){
                $ReceiptRes = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post($VerifyReceiptUrl, [
                        'password'      => env('IN_APP_PURCHASE_PASSWORD'), 
                        'receipt-data'  => $UserSubPlan->receipt_id
                    ]);
                

            
                if($ReceiptRes->status()!=200){
                    $data = General::setResponse('SUCCESS', "Somthing went wrong");
                    $data['data'] = (object)[];
                    $data['plans'] = $this->PlansRep->GetPlans();
                    return $data;
                }

                $InAppPurchaseRes = json_decode($ReceiptRes->body());

                if($InAppPurchaseRes->status!==0){
                    $data = General::setResponse('SUCCESS', "Somthing went wrong with in app purchase.");
                    $data['data'] = (object)[];
                    $data['plans'] = $this->PlansRep->GetPlans();
                    return $data;
                }

                $UserSubPlan->subscription_start_date   = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->latest_receipt_info[0]->purchase_date));
                $UserSubPlan->subscription_end_date     = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->latest_receipt_info[0]->expires_date));
                $UserSubPlan->ios_product_id            = $UserSubPlan->plan->ios_product_id;
                $UserSubPlan->android_product_id            = $UserSubPlan->plan->android_product_id;
                
                $CurrentPlan    = $this->UserSubscriptionsRep->GetUserPlan();
                $PlanObj = Plans::where('ios_product_id',$InAppPurchaseRes->latest_receipt_info[0]->product_id)->first();
                $CurrentPlanID  = (!empty($CurrentPlan)) ? $CurrentPlan->id : 0;
                
                if($UserSubPlan->subscription_end_date > date('Y-m-d H:i:s')){
                    $UserSubPlan->status = 1;
                }else{
                    $UserSubPlan->status = 0;
                }
                
                $response = $this->UserSubscriptionsRep->AddEditUserSubscription($PlanObj->id, $UserSubPlan->receipt_id , $UserSubPlan->subscription_start_date , $UserSubPlan->subscription_end_date, $CurrentPlanID, $ReceiptRes->body());
                unset($UserSubPlan->plan);

                $data = General::setResponse('SUCCESS', "Success.");
                $data['data'] = $UserSubPlan;
                $data['plans'] = $this->PlansRep->GetPlans();
                return $data;

            }else{
                $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();
                
                if(date('Y-m-d H:i:s') > $google_auth_refresh_token->token_expiry_time){
                    General::getGoogleAuthRefreshToken();
                    $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();
                }

                $purchase_response = Http::withHeaders([
                    'Authorization'     => 'Bearer' . ' ' .  $google_auth_refresh_token['access_token'],
                    'Content-Type' => 'application/json',
                ])->get(env('SUB_URL').$UserSubPlan->purchase_token);
                
                if($purchase_response->status()!=200){
                    $message ="Somthing went wrong";
                    
                    $data = General::setResponse('SUCCESS', $message);
                    $data['data'] = (object)[];
                    $data['plans'] = $this->PlansRep->GetPlans();
                    return $data;
                }
                
                $InAppPurchaseRes = json_decode($purchase_response->body());
                
                $LineItems = $InAppPurchaseRes->lineItems[0];

                $StartDate = date('Y-m-d H:i:s', strtotime($InAppPurchaseRes->startTime));
                $EndDate = date('Y-m-d H:i:s', strtotime($LineItems->expiryTime));

                if ($EndDate > date('Y-m-d H:i:s')){
                    $status = 1;
                }else{
                    $status = 0;
                }

                $response = $this->UserSubscriptionsRep->AndroidAddEditUserSubscription($UserSubPlan->plan_id, $UserSubPlan->purchase_token, $StartDate, $EndDate, $status, $UserSubPlan->id);

                $UserSubPlan->subscription_start_date   = $StartDate;
                $UserSubPlan->subscription_end_date     = $EndDate;
                $UserSubPlan->status                    = $status;
                $UserSubPlan->ios_product_id            = $UserSubPlan->plan->ios_product_id;
                $UserSubPlan->android_product_id            = $UserSubPlan->plan->android_product_id;
                unset($UserSubPlan->plan);

                $data = General::setResponse('SUCCESS', "Success.");
                $data['data'] = $UserSubPlan;
                $data['plans'] = $this->PlansRep->GetPlans();
                return $data;

            }

        } catch (Exception $e) {
            DB::rollback();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
