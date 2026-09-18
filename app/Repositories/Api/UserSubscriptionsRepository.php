<?php
namespace App\Repositories\Api;

use App\Models\UserSubscriptions;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;

class UserSubscriptionsRepository extends BaseRepository {

    public function model() {
        return UserSubscriptions::class;
    }

    public function GetUserSubscriptions(){
        return $this->model->get();
    }

    public function GetUserPlan($userId = null){
        $userId = $userId ?: (Auth::check() ? Auth::id() : null);
        if (!$userId) return null;
        return $this->model->where('user_id', $userId)
            ->with('plan')
            ->orderBy('subscription_end_date', 'DESC')
            ->first();
    }

    public function GetBusinessPlan($businessId){
        if (!$businessId) return null;
        return $this->model->where('business_id', $businessId)
            ->with('plan')
            ->orderBy('subscription_end_date', 'DESC')
            ->first();
    }

    public function GetUserCurrentPlan(){
        $CurrentDate = date('Ymd');
        return $this->model->where('user_id', Auth::user()->id)
            ->with('plan')
            ->whereRaw('DATE_FORMAT(subscription_end_date, "%Y%m%d") >= '.$CurrentDate)
            ->orderBy('subscription_end_date', 'ASC')
            ->first();
    }

    public function AddEditUserSubscription($PlanID, $ReceiptID, $StartDate, $EndDate, $CurrentPlanID, $json = null){
        $plan = \App\Models\Plans::find($PlanID);

        $Data = [];
        $Data['user_id']                 = Auth::user()->id;
        $Data['user_name']               = Auth::user()->name;
        $Data['user_email']              = Auth::user()->email;
        $Data['plan_id']                 = $PlanID;
        $Data['receipt_id']              = $ReceiptID;
        $Data['purchase_from']           = '0';
        $Data['purchase_token']          = null;
        $Data['subscription_start_date'] = $StartDate;
        $Data['subscription_end_date']   = $EndDate;
        $Data['amount']                  = $plan ? $plan->price : 0.00;
        $Data['payment_status']          = 'completed';
        $Data['status']                  = 1;
        $Data['notes']                   = 'iOS In-App Purchase';

        if($CurrentPlanID > 0){
            return $this->model->where('id', $CurrentPlanID)->update($Data);
        } else {
            return $this->model->create($Data);
        }
    }

    public function AndroidAddEditUserSubscription($PlanID, $purchase_token, $StartDate, $EndDate, $status, $CurrentPlanID){
        $plan = \App\Models\Plans::find($PlanID);

        $Data                            = [];
        $Data['user_id']                 = Auth::user()->id;
        $Data['user_name']               = Auth::user()->name;
        $Data['user_email']              = Auth::user()->email;
        $Data['plan_id']                 = $PlanID;
        $Data['receipt_id']              = null;
        $Data['purchase_token']          = $purchase_token;
        $Data['subscription_start_date'] = $StartDate;
        $Data['subscription_end_date']   = $EndDate;
        $Data['status']                  = $status;
        $Data['payment_status']          = ($status == 1) ? 'completed' : 'pending';
        $Data['amount']                  = $plan ? $plan->price : 0.00;
        $Data['purchase_from']           = '1';
        $Data['notes']                   = 'Android In-App Purchase';

        if($CurrentPlanID > 0){
            return $this->model->where('id', $CurrentPlanID)->update($Data);
        } else {
            return $this->model->create($Data);
        }
    }
}
