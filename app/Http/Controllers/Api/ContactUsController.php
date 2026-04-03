<?php

namespace App\Http\Controllers\Api;

use App\Classes\Api\ContactUsCls;
use App\General\General;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    protected $ContactUsCls;

    public function __construct(ContactUsCls $ContactUsCls)
    {
        $this->ContactUsCls = $ContactUsCls;
    }

    /**
     * Submit a contact us request.
     */
    public function Submit(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->ContactUsCls->Submit($postData);
        return get_response($request, $data);
    }
}
