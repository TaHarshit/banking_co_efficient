<?php

namespace App\Repositories\Api;

use App\Models\Notification;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;

class NotificationsRepository extends BaseRepository {

    public function model() {
        return Notification::class;
    }
    
    public function GetNotification($id){
        return $this->model->find($id);
    }

    public function GetNotifications(){
        return $this->model->where('user_id', auth()->id())->get();
    }

    public function StoreNotification($user_id, $title, $message){
        return $this->model->create([
                'user_id'=>$user_id,
                'title'=>$title,
                'message'=>$message
            ]);
    }
    
}
