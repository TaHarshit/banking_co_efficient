<?php

namespace App\Repositories\Api;

use App\Models\Plans;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;

class PlansRepository extends BaseRepository {

    public function model() {
        return Plans::class;
    }
    
    public function GetPlan($id){
        return $this->model->find($id);
    }

    public function GetPlans(){
        return $this->model->where('status', 1)->where('type', 0)->get();
    }
}
