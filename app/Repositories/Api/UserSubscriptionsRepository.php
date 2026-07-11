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

    public function GetUserPlan(){
        return $this->model->where('user_id', auth()->user()->id)
            ->orderBy('subscription_end_date', 'DESC')
            ->first();
    }

    public function GetUserCurrentPlan(){
        $CurrentDate = date('Ymd');
        return $this->model->where('user_id', Auth::user()->id)
            ->whereRaw('DATE_FORMAT(subscription_end_date, "%Y%m%d") >= '.$CurrentDate)
            ->orderBy('subscription_end_date', 'ASC')
            ->first();
    }

    public function AddEditUserSubscription($PlanID, $ReceiptID, $StartDate, $EndDate, $CurrentPlanID, $json){
        
        $Data = [];
        $Data['user_id']        = Auth::user()->id;
        $Data['user_name']      = Auth::user()->name;
        $Data['user_email']     = Auth::user()->email;
        $Data['plan_id']        = $PlanID;
        $Data['receipt_id']     = $ReceiptID;
        $Data['purchase_from']  = 0;
        $Data['subscription_start_date']    = $StartDate;
        $Data['subscription_end_date']      = $EndDate;

        if($CurrentPlanID>0){
             $Data['purchase_token']     = null;
            return $this->model->where('id', $CurrentPlanID)->update($Data);
        } else {
            $Data['purchase_token']     = null;
            return $this->model->create($Data);
        }
    }

    public function AndroidAddEditUserSubscription($PlanID, $purchase_token, $StartDate, $EndDate, $status, $CurrentPlanID){

        $Data                                   = [];
        $Data['user_id']                        = auth()->user()->id;
        $Data['user_name']                      = auth()->user()->name;
        $Data['user_email']                     = auth()->user()->email;
        $Data['plan_id']                        = $PlanID;
        $Data['purchase_token']                 = $purchase_token;
        $Data['subscription_start_date']        = $StartDate;
        $Data['subscription_end_date']          = $EndDate;
        $Data['status']                         = $status;
        $Data['purchase_from']                  = 1;

        if($CurrentPlanID>0){
            $Data['receipt_id']     = '';
            return $this->model->where('id', $CurrentPlanID)->update($Data);
        } else {
            
            $Data['receipt_id']     = '';
            return $this->model->create($Data);
        }

    }
}
