<?php

if (!function_exists('config_path')) {

    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '') {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

}

if (!function_exists('public_path')) {
    /**
     * Return the path to public dir
     *
     * @param null $path
     *
     * @return string
     */
    function public_path($path = null)
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }
}

if(!function_exists('addLog')){

    function addLog($request='', $response='')
    {
        $cyd        = date('Y/m');
        $cf         = 'public/api-log/'.$cyd;
        $fname      = $cf.'/logs-'.date('Y-m-d').'.html';
        $datetime   = date('YmdHis');

        if(!is_dir($cf)) {  @mkdir($cf, 0777, true); }  
        if(!file_exists($fname)){
            $html="<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css'>
                    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
                    <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js'></script>
                    <style>
                        body{ font-size:16px; }
                        .main_div { background: #efefef;border-bottom: 4px solid #D32121;box-shadow: 0 -1px 2px #B89595; }
                        .request_div { background:#93E0EA;padding:10px 10px; }
                        .req_span{ background: #93EAA8; }
                        .req_para { background: #93E0EA;margin-top:10px;margin-bottom:5px; overflow-wrap: break-word;}
                        .res{ background: #93EAA8;padding: 10px; overflow-wrap: break-word;}
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>".env('APP_NAME')." Api Log :</h2>
                        <div class='panel-group'>
                        </div>
                    </div>
                </body>
            </html>";
            file_put_contents($fname, $html.PHP_EOL , FILE_APPEND | LOCK_EX);
        } 
        $lines = array();
    
        $html1='<div class="panel panel-default">
                    <div class="panel-heading">
                        <a data-toggle="collapse" href="#'.$datetime.'" style="text-decoration:none; cursor:pointer;">
                            <h4 class="panel-title">
                                <span class="btn btn-danger">'.$request->method().'</span>
                                <span class="btn btn-primary">'.date('Y-m-d H:i:s').'</span>
                                <span class="btn btn-success">'.$request->url().'</span>
                            </h4>
                        </a>
                    </div>
                    <div id="'.$datetime.'" class="panel-collapse collapse in">
                        <div class="panel-body" style="padding:0;">
                            <div class="text-white bg-info" style="margin-bottom:2px;padding:15px;">
                                <div style="margin-bottom:10px;"><b> Request Headers :</b><br>'.json_encode(collect($request->header())->toArray()).'</div>
                                <div style="margin-bottom:10px;"><b>GET Request : </b><br>'.json_encode($request->query()).'</div>
                                <div style="margin-bottom:10px;"> <b>POST Request :</b><br>'.json_encode($request->post()).'</div>
                            </div>
                            <div class="text-white bg-success" style="padding:15px;">
                                <b>Json Response :</b> <br>'.json_encode($response).'
                            </div>
                        </div>
                    </div>
                </div>';
        $html1.="\n";
        
        foreach(file($fname) as $line) {
            array_push($lines, $line);
            if(strpos($line, "<div class='panel-group'>") !== FALSE){ array_push($lines, $html1); }
        }
        //-flude
        $myfile = file_put_contents($fname, $lines);
    }
}

if (!function_exists('buildTree')) {

    function buildTree(array $elements, $parentId = 0) {

        $branch = array();

        foreach ($elements as $element) {
            if ($element->parentId == $parentId) {
                $children = buildTree($elements, $element->id);
                if ($children) {
                    $element->subMenu = $children;
                }
                $branch[] = $element;
            }
        }
        /*foreach ($elements as $element) {
            if ($element['parentId'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                if ($children) {
                    $element['subMenu'] = $children;
                }
                $branch[] = $element;
            }
        }*/

        return $branch;
    }
}

if (!function_exists('setResponse')) {

    /**
     * This function is used to set response content and response code
     */
    function setResponse($type, $message = '', $result = "") {
        $successFlag = false;
        $requestHeader = app('Illuminate\Http\Request');
        switch (strtoupper($type)) {
            case 'SUCCESS':
                $code = 200;
                $successFlag = true;
                break;
            case 'NO_CONTENT':
                $code = 204;
                $message = 'No data found';
                break;
            case 'VALIDATION_ERROR':
                $code = 422;
                break;
            case 'BAD_REQUEST':
                $code = 400;
                break;
            case 'OTHER_ERROR':
                $code = 423;
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
            case 'NOT_ACCEPTABLE':
                $code = 406;
                break;
            case 'BAD_REQUEST':
                $code = 400;
                break;
            default:
                break;
        }

        $data = array('code' => $code, 'message' => $message);
        if (!empty($result)) {
            $data['result'] = $result;
        }
        /*if ($requestHeader->has('expiry_time')) {
            $response['expiry_time'] = date("Y-m-d H:i:s", strtotime($requestHeader->expiry_time));
        }*/
        //$data['response'] = $response;
        return $data;
    }

}

if(!function_exists('get_response'))
{
    function get_response($request, $response)
    {
        $code = $response['code'];
        unset($response['code']);
        addLog($request, $response);
        return response($response, $code);
    }
}

if (!function_exists('GetImage')) {
    function GetImage($file_name, $file_path) {
        if($file_name) {
            return asset('public/storage/'.$file_path.'/'.$file_name);
        } else {
            return asset('public/no-image.png');
        }
    }
}

if (!function_exists('SingleImageUpload')) {
    function SingleImageUpload($file, $folderName) {
        $file_name = rand().time().'.'.$file->getClientOriginalExtension();  
        $file->move(public_path('uploads/'.$folderName), $file_name);
        return $file_name;
    }
}

if (!function_exists('SingleImageRemove')) {
    function SingleImageRemove($file_name, $folderName) {
        Storage::disk('uploads')->delete($file_path. '/'. $file_name);
        return true;
    }
}
if (!function_exists('logAdminActivity')) {
    /**
     * Log admin activity
     * 
     * @param string $module
     * @param string $action
     * @param int|null $moduleId
     * @param string|null $description
     * @param array|null $data
     */
    function logAdminActivity($module, $action, $moduleId = null, $description = null, $data = null) {
        try {
            $adminId = auth()->check() ? auth()->id() : null;
            
            // Log only if it's an admin (user_type 1)
            // Or if you want to log all auth users actions in admin panel
            
            \App\Models\AdminActivityLog::create([
                'admin_id' => $adminId,
                'module' => $module,
                'action' => $action,
                'module_id' => $moduleId,
                'description' => $description,
                'data' => $data,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log admin activity: ' . $e->getMessage());
        }
    }
}
