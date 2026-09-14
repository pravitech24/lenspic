<?php

namespace App\Services\Branding;

use App\Models\{BusinessBranding,Group,User};

class BusinessBrandingPresenter
{
    public function settings(User$user):array
    {
        $branding=$user->businessBranding;
        $legacy=$user->meta??[];
        return [
            'business_name'=>$branding?->business_name??$legacy['studio_name']??'',
            'business_phone_country_code'=>$branding?->business_phone_country_code??'+91','business_phone_number'=>$branding?->business_phone_number??'',
            'show_business_phone_in_gallery'=>$branding?->show_business_phone_in_gallery??false,
            'business_email'=>$branding?->business_email??$legacy['business_email']??'','show_business_email_in_gallery'=>$branding?->show_business_email_in_gallery??false,
            'website'=>$branding?->website??$legacy['website']??'','show_website_in_gallery'=>$branding?->show_website_in_gallery??false,
            'instagram_url'=>$branding?->instagram_url??'','show_instagram_in_gallery'=>$branding?->show_instagram_in_gallery??false,
            'facebook_url'=>$branding?->facebook_url??'','show_facebook_in_gallery'=>$branding?->show_facebook_in_gallery??false,
            'whatsapp_country_code'=>$branding?->whatsapp_country_code??'+91','whatsapp_phone_number'=>$branding?->whatsapp_phone_number??'',
            'show_whatsapp_in_portfolio'=>$branding?->show_whatsapp_in_portfolio??false,
            'youtube_url'=>$branding?->youtube_url??'','show_youtube_in_portfolio'=>$branding?->show_youtube_in_portfolio??false,
            'vimeo_url'=>$branding?->vimeo_url??'','show_vimeo_in_portfolio'=>$branding?->show_vimeo_in_portfolio??false,
            'has_logo'=>$branding?->hasLogo()??false,'logo_url'=>$branding?->hasLogo()?route('settings.business-branding.logo'):null,
        ];
    }
    public function gallery(Group$group):array
    {
        $b=$group->creator->businessBranding;if(!$b)return[];
        return array_filter([
            'business_name'=>$b->business_name,
            'phone'=>$b->show_business_phone_in_gallery?trim(($b->business_phone_country_code??'').' '.($b->business_phone_number??'')):null,
            'email'=>$b->show_business_email_in_gallery?$b->business_email:null,
            'website'=>$b->show_website_in_gallery?$b->website:null,
            'instagram_url'=>$b->show_instagram_in_gallery?$b->instagram_url:null,
            'facebook_url'=>$b->show_facebook_in_gallery?$b->facebook_url:null,
            'logo_url'=>$b->hasLogo()?route('guest.branding.logo',$group):null,
        ],fn($value)=>filled($value));
    }
    public function portfolio(User$user):array
    {
        $b=$user->businessBranding;if(!$b)return[];
        return array_filter([
            'whatsapp_url'=>config('whatsapp.portfolio_enabled') && $b->show_whatsapp_in_portfolio ? app(\App\Services\WhatsAppLinks::class)->contact($b->whatsapp_phone_number, $b->whatsapp_country_code, 'Hello, I found your photography portfolio on LensPic.') : null,
            'whatsapp'=>config('whatsapp.portfolio_enabled') && $b->show_whatsapp_in_portfolio?trim(($b->whatsapp_country_code??'').' '.($b->whatsapp_phone_number??'')):null,
            'youtube_url'=>$b->show_youtube_in_portfolio?$b->youtube_url:null,
            'vimeo_url'=>$b->show_vimeo_in_portfolio?$b->vimeo_url:null,
        ],fn($value)=>filled($value));
    }
}
