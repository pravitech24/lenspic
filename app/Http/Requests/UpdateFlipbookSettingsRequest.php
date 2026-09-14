<?php
namespace App\Http\Requests;
use App\Services\Team\TeamAuthorization;
use Illuminate\Foundation\Http\FormRequest;
class UpdateFlipbookSettingsRequest extends FormRequest {
 public function authorize():bool{$owner=app(TeamAuthorization::class)->ownerFor($this->user());return$owner&&app(TeamAuthorization::class)->allows($this->user(),$owner,'manage_branding');}
 protected function prepareForValidation():void{$this->merge(['business_name'=>filled($this->input('business_name'))?trim(preg_replace('/\s+/u',' ',(string)$this->input('business_name'))):null]);}
 public function rules():array{return['business_name'=>['nullable','string','max:150'],'logo'=>['nullable','file','mimes:png,jpg,jpeg,webp','mimetypes:image/png,image/jpeg,image/webp','max:2048'],'remove_logo'=>['sometimes','boolean'],'apply_to_portfolio'=>['required','boolean']];}
 public function messages():array{return['logo.max'=>'Please select an image smaller than 2 MB.','logo.mimes'=>'The selected logo format is not supported.','logo.mimetypes'=>'The selected logo format is not supported.'];}
}
