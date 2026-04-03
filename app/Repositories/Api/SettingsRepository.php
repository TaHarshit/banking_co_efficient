<?php
namespace App\Repositories\Api;

// use App\General\General;
use App\Models\Settings;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use URL;
use Auth;

class SettingsRepository extends BaseRepository {

    public function model() {
        return Settings::class;
    }

    public function GetSettings(){
        return $this->model->find(1);
    }
}
