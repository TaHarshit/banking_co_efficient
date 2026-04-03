<?php

namespace App\Http\Controllers\Api;

use App\Classes\Api\NotificationsCls;
use App\General\General;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    protected $NotificationCls;

    public function __construct(NotificationsCls $NotificationCls)
    {
        $this->NotificationCls = $NotificationCls;        
    }

    public function StoreNotification(Request $request){
        $postData 	= General::stripRequest($request->all());
        $data = $this->NotificationCls->StoreNotification($postData);
        return get_response($request, $data);
    }

    public function GetNotifications(Request $request){
        $postData   = General::stripRequest($request->all());
        $data = $this->NotificationCls->GetNotifications();
        return get_response($request, $data);
    }
}