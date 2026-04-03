<?php

namespace App\Classes\Api;

use App\General\General;
use App\Repositories\Api\NotificationsRepository;
use Exception;

Class NotificationsCls {
    
    protected $NotificationRep;

    public function __construct(NotificationsRepository $NotificationRep)
    {
        $this->NotificationRep = $NotificationRep;
    }

    public function StoreNotification($postData){
        try{
            $responce = $this->NotificationRep->StoreNotification($postData);
            
            $data = General::setResponse('SUCCESS', "success");
            $data['data'] = $responce;
            return $data;

        }catch(Exception $e){
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
    
    public function GetNotifications(){
        try{
            $responce = $this->NotificationRep->GetNotifications();
            
            $data = General::setResponse('SUCCESS', "Notifications get successfully.");
            $data['data'] = $responce;
            return $data;
            
        }catch(Exception $e){
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

}