<?php
namespace App\Services\Branding;
use App\Models\{Group,User};
class FlipbookBrandingPresenter {
 public function settings(User$owner):array{$s=$owner->flipbookSetting;$fallback=$owner->businessBranding?->business_name?:$owner->studio_name;return['business_name'=>$s?->business_name??$fallback,'apply_to_portfolio'=>$s?->apply_to_portfolio??false,'has_logo'=>$s?->hasLogo()??false,'logo_url'=>$s?->hasLogo()?route('settings.flipbook.logo'):null];}
 public function flipbook(Group$group):array{$s=$group->creator->flipbookSetting;return['business_name'=>$s?->business_name?:$group->creator->businessBranding?->business_name?:$group->creator->studio_name,'logo_url'=>$s?->hasLogo()?route('guest.flipbook.logo',$group):null];}
 public function portfolio(User$owner):array{$s=$owner->flipbookSetting;if($s?->apply_to_portfolio)return['business_name'=>$s->business_name?:$owner->studio_name,'logo_url'=>$s->hasLogo()?route('settings.flipbook.logo'):null,'source'=>'flipbook'];$b=$owner->businessBranding;return['business_name'=>$b?->business_name?:$owner->studio_name,'logo_url'=>$b?->hasLogo()?route('settings.business-branding.logo'):null,'source'=>'portfolio'];}
}
