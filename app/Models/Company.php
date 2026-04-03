<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_code',
        'email',
        'address',
        'contact_no',
        'about_us',
        'company_logo',
        'business_image_1',
        'business_image_2',
        'website_link',
        'van_ar_img',
        'van_ar_img_2',
        'van_ar_img_3',
        'cab_color',
        'background_color',
        'van_ar_code',
        'approval_btn'
    ];

}
