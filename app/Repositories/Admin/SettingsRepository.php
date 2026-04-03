<?php
namespace App\Repositories\Admin;

use App\Models\Settings;
use App\Repositories\BaseRepository;

class SettingsRepository extends BaseRepository {

    public function model() {
        return Settings::class;
    }

    public function GetSetting($id){
        return $this->model->find($id);
    }

    public function StoreSetting($user_android_version, $user_ios_version, $android_build_number, $ios_build_number, $privacy_policy, $terms_and_conditions, $id){
        
        $data = [];
        $data['user_android_version']   = $user_android_version;
        $data['user_ios_version']       = $user_ios_version;
        $data['android_build_number']   = $android_build_number;
        $data['ios_build_number']       = $ios_build_number;
        $data['privacy_policy']         = $privacy_policy;
        $data['terms_and_conditions']   = $terms_and_conditions;

        if($id>0){
            return $this->model->where('id', $id)->update($data);
        } else {
            return $this->model->create($data);
        }
    }
}
