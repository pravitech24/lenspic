<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessBrandingRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('create',\App\Models\Group::class)===true;}
    protected function prepareForValidation():void
    {
        $this->merge(collect(['business_phone_number','whatsapp_phone_number'])->mapWithKeys(fn($field)=>[$field=>filled($this->input($field))?preg_replace('/[\s().-]+/','',(string)$this->input($field)):null])->all());
    }
    public function rules():array{return[
        'business_name'=>['nullable','string','max:100'],
        'business_phone_country_code'=>['nullable','required_with:business_phone_number','regex:/^\+[1-9]\d{0,3}$/'],
        'business_phone_number'=>['nullable','regex:/^[1-9]\d{6,14}$/'],
        'show_business_phone_in_gallery'=>['required','boolean'],
        'business_email'=>['nullable','email:rfc','max:255'],'show_business_email_in_gallery'=>['required','boolean'],
        'website'=>['nullable','url:http,https','max:500'],'show_website_in_gallery'=>['required','boolean'],
        'instagram_url'=>['nullable','url:http,https','max:500','regex:/^https?:\/\/(www\.)?instagram\.com\//i'],'show_instagram_in_gallery'=>['required','boolean'],
        'facebook_url'=>['nullable','url:http,https','max:500','regex:/^https?:\/\/(www\.)?(facebook\.com|fb\.com)\//i'],'show_facebook_in_gallery'=>['required','boolean'],
        'whatsapp_country_code'=>['nullable','required_with:whatsapp_phone_number','regex:/^\+[1-9]\d{0,3}$/'],
        'whatsapp_phone_number'=>['nullable','string','max:30', function ($attribute, $value, $fail) { try { app(\App\Services\WhatsAppLinks::class)->normalize($value, $this->input('whatsapp_country_code')); } catch (\InvalidArgumentException) { $fail('Enter a valid WhatsApp number.'); } }],'show_whatsapp_in_portfolio'=>['required','boolean'],
        'youtube_url'=>['nullable','url:http,https','max:500','regex:/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\//i'],'show_youtube_in_portfolio'=>['required','boolean'],
        'vimeo_url'=>['nullable','url:http,https','max:500','regex:/^https?:\/\/(www\.)?vimeo\.com\//i'],'show_vimeo_in_portfolio'=>['required','boolean'],
        'logo'=>['nullable','file','mimes:jpg,jpeg,png','mimetypes:image/jpeg,image/png','max:5120'],
        'remove_logo'=>['sometimes','boolean'],
    ];}
}
