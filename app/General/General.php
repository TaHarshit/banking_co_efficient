<?php

namespace App\General;

use App\Models\InAppAuthToken;
use App\Repositories\Api\NotificationsRepository;
use Carbon\Carbon;
use App\Models\Notification;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;

class General extends \Exception
{  
    protected $NotificationsRep;
    
    public function __construct(NotificationsRepository $NotificationsRep)
    {   
        $this->NotificationsRep = $NotificationsRep;
    }

    public static function stripRequest($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = self::stripRequest($value);
                } else {
                    $data[$key] = trim(strip_tags($value));
                }
            }
        }
        return $data;
    }

    public static function from_camel_case($tests)
    {
        $poArray = [];
        foreach ($tests as $test => $result) {
            $output = self::inner_camel_case($test);
            $poArray[$output] = $result;
        }
        return $poArray;
    }

    public static function inner_camel_case($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }


    public static function camelCase($apiResponseArray, $isSingleArr = false)
    {
        $finalArr = array();
        if (!$isSingleArr) {
            foreach ($apiResponseArray as $key => $val) {
                foreach ($val as $key1 => $testval) {
                    if (is_null($testval)) {
                        $apiResponseArray[$key][$key1] = "";
                    }
                }
            }
            foreach ($apiResponseArray as $key1 => $val1) {
                $keys = array_map(function ($i) {
                    $parts = explode('_', $i);
                    return array_shift($parts) . implode('', array_map('ucfirst', $parts));
                }, array_keys($val1));
                array_push($finalArr, array_combine($keys, $val1));
            }
        } else {

            $keys = array_map(function ($i) {
                $parts = explode('_', $i);
                return array_shift($parts) . implode('', array_map('ucfirst', $parts));
            }, array_keys($apiResponseArray));
            array_push($finalArr, array_combine($keys, $apiResponseArray));
        }
        return $finalArr;
    }

    public static function setResponse($type, $message = '')
    {
        switch (strtoupper($type)) {
            case 'SUCCESS':
                $code = 200;
                break;
            case 'NO_CONTENT':
                $code = 204;
                $message = ($message!='') ? $message : __('messages.no_data');
                break;
            case 'VALIDATION_ERROR':
                $code = 422;
                break;
            case 'OTHER_ERROR':
                $code = 423;
                if (!isset($message))
                    $message = __('messages.something_wrong');
                break;
            case 'FORBIDDEN':
                $code = 403;
                break;
            case 'UNAUTHORIZED':
                $code = 401;
                break;
            case 'NOT_FOUND':
                $code = 404;
                break;
            case 'ALREADY_EXISTS':
                $code = 444;
                break;
            case 'NOT_MATCHED':
                $code = 406;
                break;
            case 'GONE':
                $code = 410;
                break;
            case 'USED':
                $code = 206;
                break;
            case 'CANCELATION_THRESHOLD':
                $code = 488;
                break;
            case 'SESSION_EXPIRED':
                $code = 440;
                break;
            default:
                break;
        }

        $data['code']       = $code;
        $data['message']    = $message;
        $data['data']       = (object)[];
        return $data;
    }

    public static function LookingFor($id){
        switch ($id){
            case '0':
                $name = 'Long-term relationship';
                break;
            case '1':
                $name = 'Marriage';
                break;
            case '2':
                $name = 'Casual Fun';
                break;
            case '3' : 
                $name = 'Drinking/dining buddies';
                break;
            case '4' : 
                $name = 'Good Friends';
        }

        return $name;
    }

    public static function checkImage($image, $type, $mediaType = 'Image', $requestId = 0)
    {
        $imagePath = '';
        $checkImagePath = '';
        if ($type == 'user') {
            $checkImagePath     = storage_path('app/public/profile_image/');
            $imagePath          = asset('storage/app/public/profile_image');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        } else if($type == 'category'){
            $checkImagePath     = storage_path('app/public/category_image/');
            $imagePath          = asset('storage/app/public/category_image');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        } else if($type == 'company_logo'){
            $checkImagePath     = storage_path('app/public/company_logo/');
            $imagePath          = asset('storage/app/public/company_logo');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }else if($type == 'business_image_1'){
            $checkImagePath     = storage_path('app/public/business_image/');
            $imagePath          = asset('storage/app/public/business_image');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }else if($type == 'business_image_2'){
            $checkImagePath     = storage_path('app/public/business_image/');
            $imagePath          = asset('storage/app/public/business_image');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }else if($type == 'background_image'){
            $checkImagePath     = storage_path('app/public/background_image/');
            $imagePath          = asset('storage/app/public/background_image');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }else if($type == 'home_ar_img'){
            $checkImagePath     = storage_path('app/public/home_ar_img/');
            $imagePath          = asset('storage/app/public/home_ar_img');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }else if($type == 'van_ar_img'){
            $checkImagePath     = storage_path('app/public/van_ar_img/');
            $imagePath          = asset('storage/app/public/van_ar_img');
            $placeHolderImage   = $imagePath.'/placeholder.jpg';
        }

        if (!is_null($image)) {
            //dd($checkImagePath);
            if (file_exists($checkImagePath.$image)) {
                return $imagePath .'/' .$image;
            } else {
                return $placeHolderImage;
            }
        } else {
            return $placeHolderImage;
        }
    }

    public static function dateDiffInDays($date1, $date2)
    {
        // Calculating the difference in timestamps
        $diff = strtotime($date2) - strtotime($date1);

        // 1 day = 24 hours
        // 24 * 60 * 60 = 86400 seconds
        return round($diff / 86400);
    }

    public static function getMonthsCountFromDate($fromDate, $dayBeforeCheckIn, $PaymentDuration)
    {
        // Calculating the difference in timestamps
        $last_date      = strtotime("-".$dayBeforeCheckIn." days", strtotime($fromDate));
        $diff           = $last_date-strtotime("+7 day", strtotime(date("Y-m-d")));
        $noInstallment  = (round($diff / 86400)) / $PaymentDuration;
        $no_installment = 0;

        if($noInstallment>0){
            $noInstallmentArray = explode('.', $noInstallment);
            $noInstallment      = (count($noInstallmentArray)>0) ? $noInstallmentArray[0]+1 : $noInstallment;
            $no_installment     = round($noInstallment);
        } 
        return $no_installment;
    }

    public static function GetTimeDiff($date1, $date2)
    {
        $return = "";
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        $diff_min = $interval->i;
        if($diff_min>0 && $diff_min<=9) { $min_diff = "0".$diff_min; } else { $min_diff = $diff_min; }

        if ( $v = $interval->y >= 1 ) { $return = $interval->y.' year'; }
        elseif ( $v = $interval->m >= 1 ) { $return = $interval->m.' month'; }
        elseif ( $v = $interval->d >= 1 ) { $return = $interval->d.' day'; }
        //elseif ( $v = $interval->h >= 1 ) { $return = $interval->h.":".$min_diff.' hour'; }
        elseif ( $v = $interval->h >= 1 ) { $return = $interval->h.' hour'; }
        elseif ( $v = $interval->i >= 1 ) { $return = $min_diff.' minute'; }
        else { $return = $interval->s.' second'; }
        
        return $return;
    }

    public static function sendNotification($user_id, $title, $message, $payload)
    {
        try{
            $user           = User::select('device_token', 'platform')->where('id', $user_id)->first();
            $receiver_id    = $user->device_token;
            $type           = $user->platform;

            if($type!='IOS') { 
                
                $msg = array('vibrate'=>0, 'sound'=>'default', 'title'=>$title, 'message'=>$message);
                $msg = array_merge($msg, $payload);
                $fcmFields = array(
                    'registration_ids'  => is_array($receiver_id) ? $receiver_id : array($receiver_id), 
                    'priority'          => 'high', 
                    'data'              => $msg
                );

            } else { 
                
                $fcmFields = array(
                    'registration_ids' => is_array($receiver_id) ? $receiver_id : array($receiver_id), 
                    'priority'      => 'high', 
                    'sound'         => 'default', 
                    'notification'  => array(
                        "title_loc_key" =>  $title,
                        'sound'         => 'default',
                        "body_loc_key"  => $message,
                        "badge"         => 1
                    ),
                    'data' => $payload
                ); 
            }

            $url        = 'https://fcm.googleapis.com/fcm/send';
            $server_key = config('services.fcm.server_key');
            $headers    = array('Content-Type:application/json', 'Authorization:key='.$server_key);
            $ch         = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
            $result = curl_exec($ch);
            if ($result === FALSE) { return 'Oops! FCM Send Error: '.curl_error($ch); } else { return $result; }
            curl_close($ch);

        } catch (Exception $e){
            return 'Something went wrong.';
        }
    } 
    
    public static function sendNotificationV1($user_id, $title, $message, array $payload = [])
    {
        log::debug('user id is ',[$user_id, $title, $message, $payload]);

        try{
            $user           = User::select('device_token', 'platform')->where('id', $user_id)->first();
            $receiver_id    = $user->device_token;
            $type           = $user->platform;

            if($type!='IOS') { 
                
                $stringPayload = [];
                foreach ($payload as $key => $value) {
                    $stringPayload[$key] = (string) $value;
                }

                $fcmFields = array(
                    'message' => [
                        'token' => $receiver_id,
                        'notification' => array(
                            "title" => $title,
                            "body" => $message
                        ),
                        'data' => $stringPayload
                    ]
                );

            } else { 
                
                $stringPayload = [];
                foreach ($payload as $key => $value) {
                    $stringPayload[$key] = (string) $value;
                }

                $fcmFields = array(
                    'message' => [
                        'token' => $receiver_id,
                        'notification' => array(
                            "title" => $title,
                            "body" => $message
                        ),
                        'data' => $stringPayload,
                        'apns' => array(
                            'payload' => array(
                                'aps' => array(
                                    'sound' => 'default',
                                    'content-available' => 1,
                                    'priority' => 1
                                )
                            )
                        )
                    ]
                );
            }

            $url        = 'https://fcm.googleapis.com/v1/projects/negomaster-5c83b/messages:send';
            $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();

                if(now()->toDateTimeString() > $google_auth_refresh_token->token_expiry_time){
                    $api = self::getGoogleAuthRefreshToken();
                    $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();
                }
                
            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer' . ' ' .  $google_auth_refresh_token['access_token']
                ])
                ->post($url, $fcmFields);

            if ($response->failed()) {
                Log::error('[FCM] Notification dispatch failed', [
                    'user_id' => $user_id,
                    'status'  => $response->status(),
                    'body'    => $response->body()
                ]);
            }
        
            Notification::create([
                'user_id'   =>$user_id, 
                'title'     =>$title, 
                'message'   =>$message]);
        
        } catch (Exception $e){
            return 'Something went wrong.';
        }
    }
    
    public static function CurlCall($url, $headers, $fields) {
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            if ($result === FALSE) { return 'Oops! FCM Send Error: '.curl_error($ch); } else { return $result; }
            curl_close($ch);
        } catch (Exception $e){
            return 'Something went wrong.';
        }
    }

    public static function getGoogleAuthAccessToken(){
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://accounts.google.com/o/oauth2/token', [
                'grant_type'                    =>  "authorization_code",
                'code'                          =>  config('services.google.code'),
                'client_id'                     =>  config('services.google.client_id'),
                'client_secret'                 =>  config('services.google.client_secret'),
                'redirect_uri'                  =>  config('services.google.redirect_url'),
            ]);
        } catch(Exception $e){
            return $e;
        }
        return ['code'                          =>  config('services.google.code'),
        'client_id'                     =>  config('services.google.client_id'),
        'client_secret'                 =>  config('services.google.client_secret'),
        'redirect_uri'                  =>  config('services.google.redirect_url'),];

        if ($response && ($response->status() == 200)) {

            // $saved_access_token = InAppAuthToken::updateOrCreate(
            //     ['identifier' => 'google'],
            //     ['refresh_token' => $response['refresh_token']],
            // );
            return $saved_access_token ? $saved_access_token : response('google auth has failed', 400);
        }

        // return response('google auth has failed1', 400);
        
    }


    public static function getGoogleAuthRefreshToken()
    {
        $saved_access_token = InAppAuthToken::where('identifier', 'google')->first();
        if ($saved_access_token) {

            // try {
                $refresh_token_response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post('https://accounts.google.com/o/oauth2/token', [
                    'grant_type'                    =>      "refresh_token",
                    'client_id'                     =>      config('services.google.client_id'),
                    'client_secret'                 =>      config('services.google.client_secret'),
                    'refresh_token'                 =>      $saved_access_token['refresh_token'],
                ]);
            // } catch (\Throwable $th) {
            //     return response('google auth has failed 1.1', 400);
            // }

            if ($refresh_token_response && ($refresh_token_response->status() == 200)) {

                $current_addtime = Carbon::now()->addSeconds(3590)->format('Y-m-d H:i:s');

                $saved_access_token = InAppAuthToken::where('identifier', 'google')->update(
                    ['access_token' => $refresh_token_response['access_token'],
                    'token_expiry_time' => $current_addtime]
                );
                
                return $saved_access_token ? $saved_access_token : response('google auth has failed2', 400);
            } else {
                Log::error('[Google Auth] Refresh token request failed', [
                    'status' => $refresh_token_response ? $refresh_token_response->status() : 'N/A',
                    'body'   => $refresh_token_response ? $refresh_token_response->body() : 'No response',
                ]);
            }
        } else {
            Log::error('[Google Auth] No saved access token found for google in database');
        }
        return response('something went wrong2', 400);
    }

    public static function GetSubscription($purchse_token){
        $google_auth_refresh_token =  InAppAuthToken::where('identifier', 'google')->first();

        if ($google_auth_refresh_token) {
                $response = Http::withHeaders([
                    'Authorization'     => 'Bearer' . ' ' .  $google_auth_refresh_token['access_token'],
                    'Content-Type' => 'application/json',
                ])->get('https://androidpublisher.googleapis.com/androidpublisher/v3/applications/com.measurementsapp.fss/purchases/subscriptionsv2/tokens/'.$purchse_token);
                
            // $response ? $response : response('Failed to get google purchases subscriptions', (int)$response->status());

            if ($response->status() == 401){
                Self::getGoogleAuthAccessToken();
            }elseif($response->status() == 200){
                return $response;
            }
           
            // store the response as an invoice
            // $invoice = $this->invoiceController->storeInAppInvoice(json_decode($response));
        
        }
        return response('something has went wrong', 400);

    }

}
