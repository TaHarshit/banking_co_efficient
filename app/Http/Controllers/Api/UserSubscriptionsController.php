<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Api\UserSubscriptionsCls;
use App\General\General;
use Carbon\Carbon;
use Auth;
use DB;

class UserSubscriptionsController extends Controller
{
    protected $UserSubscriptionsCls;
    
    public function __construct(UserSubscriptionsCls $UserSubscriptionsCls) {
        $this->UserSubscriptionsCls = $UserSubscriptionsCls;
    }
    
    public function InitPayment(Request $request){ 
     	$postData 	= General::stripRequest($request->all());
        $data 		= $this->UserSubscriptionsCls->InitPayment($postData);
        return get_response($request, $data);   
    }

    public function CompletePayment(Request $request){ 
        $postData 	= General::stripRequest($request->all());
       $data 		= $this->UserSubscriptionsCls->CompletePayment($postData);
       return get_response($request, $data);   
    }

    public function GetUserCurrentPlan(Request $request){ 
        $postData 	= General::stripRequest($request->all());
       $data 		= $this->UserSubscriptionsCls->GetUserCurrentPlan($postData);
       return get_response($request, $data);   
    }
}
