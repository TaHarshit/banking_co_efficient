<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\General\General;
use App\Classes\Admin\SettingsCls;
use App\Http\Controllers\Controller;
use Auth,DB;
use Carbon\Carbon;

class SettingsController extends Controller
{
    protected $SettingsCls;
    
    public function __construct(SettingsCls $SettingsCls) {
        $this->SettingsCls = $SettingsCls;
    }

    public function Index($id){
        $data = $this->SettingsCls->GetSetting($id);
        if ($data instanceof \Illuminate\Http\Response) {
            return $data;
        }
        if(empty($data)){
            return response()->view('error.404',[], 404);
        }
        return view('settings.addedit', compact('data'));
    }
    
    public function StoreSetting(Request $request){
        $banner = $request->file('banner');
        $show_banner = $request->has('show_banner') ? 1 : 0;
        return $this->SettingsCls->StoreSetting($request->user_android_version, $request->user_ios_version, $request->android_build_number, $request->ios_build_number, $request->privacy_policy, $request->terms_and_conditions, $request->id);   
    }
}
