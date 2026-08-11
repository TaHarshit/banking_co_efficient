<?php
namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Admin\SettingsRepository;
use App\General\Validate;
use Carbon\Carbon;
use Auth;
use Exception;

class SettingsCls {

    protected $SettingsRep;

    public function __construct(SettingsRepository $SettingsRep) {
        $this->SettingsRep = $SettingsRep;
    }
 
    public function GetSetting($id){
        try{
            return $this->SettingsRep->GetSetting($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message'=>$e->getMessage()], 500);
        }
    }

    public function StoreSetting($user_android_version, $user_ios_version, $android_build_number, $ios_build_number, $privacy_policy, $terms_and_conditions, $feedback_form_link, $privacy_policy_fr, $terms_and_conditions_fr, $feedback_form_link_fr, $id){
        try {
            $BannerImage  = "";
            if(!empty($banner)){

                $OldObj = $this->SettingsRep->GetSetting($id);
                if(Storage::exists('banner/'.$OldObj->banner)){
                    Storage::delete('banner/'.$OldObj->banner);
                }
                
                $BannerImage = rand().time().'.'.$banner->getClientOriginalExtension();
                $banner->storeAs('banner', $BannerImage);

            } else {
                if($id>0){
                    $SettingObj   = $this->SettingsRep->GetSetting($id);
                    $BannerImage  = $SettingObj->banner;
                }
            }

            $response = $this->SettingsRep->StoreSetting($user_android_version, $user_ios_version, $android_build_number, $ios_build_number, $privacy_policy, $terms_and_conditions, $feedback_form_link, $privacy_policy_fr, $terms_and_conditions_fr, $feedback_form_link_fr, $id);

            $message = ($id>0) ? 'Setting updated successfully' : 'Setting added successfully';
            Session::flash('message', $message); 
            Session::flash('icon', 'success');  
            return redirect()->route('addeditsetting', ['id'=>1]);

        } catch (Exception $e) {
            return response()->view('error.500', ['message'=>$e->getMessage()], 500);
        }
    }
}
