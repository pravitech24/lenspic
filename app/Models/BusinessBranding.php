<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessBranding extends Model
{
    protected $fillable=['user_id','business_name','business_phone_country_code','business_phone_number','show_business_phone_in_gallery','business_email','show_business_email_in_gallery','website','show_website_in_gallery','instagram_url','show_instagram_in_gallery','facebook_url','show_facebook_in_gallery','whatsapp_country_code','whatsapp_phone_number','show_whatsapp_in_portfolio','youtube_url','show_youtube_in_portfolio','vimeo_url','show_vimeo_in_portfolio','logo_disk','logo_object_key','logo_mime_type','logo_size_bytes'];
    protected $casts=['show_business_phone_in_gallery'=>'boolean','show_business_email_in_gallery'=>'boolean','show_website_in_gallery'=>'boolean','show_instagram_in_gallery'=>'boolean','show_facebook_in_gallery'=>'boolean','show_whatsapp_in_portfolio'=>'boolean','show_youtube_in_portfolio'=>'boolean','show_vimeo_in_portfolio'=>'boolean','logo_size_bytes'=>'integer'];
    protected $hidden=['logo_object_key','logo_disk'];
    public function user(){return $this->belongsTo(User::class);}
    public function hasLogo():bool{return filled($this->logo_object_key);}
}
