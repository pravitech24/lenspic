<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group && ($this->user()?->can('update', $group) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'event_type' => ['sometimes', 'required', 'string', 'max:100'],
            'event_date' => ['sometimes', 'nullable', 'date'],
            'privacy' => ['sometimes', 'required', 'in:public,private,link_only'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'membership_status' => ['sometimes', 'required', 'in:open,closed'],
            'membership_limit' => ['sometimes', 'nullable', 'integer', 'min:2', 'max:100000'],
            'anyone_with_link_can_join' => ['sometimes', 'boolean'],
            'anonymous_access_mode' => ['sometimes', 'required', 'in:disabled,face_only,full'],
            'downloads_enabled' => ['sometimes', 'boolean'],
            'favourites_enabled' => ['sometimes', 'boolean'],
            'participants_can_edit_identity' => ['sometimes', 'boolean'],
            'allow_guest_upload' => ['sometimes', 'boolean'],
            'face_recognition_enabled' => ['sometimes', 'boolean'],
            'watermark_enabled' => ['sometimes', 'boolean'],
            'watermark_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'invitation_expires_at' => ['sometimes', 'nullable', 'date'],
            'event_code_expires_at' => ['sometimes', 'nullable', 'date'],
            'cover_photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:min_width=800,min_height=300'],
            'remove_cover_photo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cover_photo.image' => 'Choose a valid JPG, PNG, or WebP cover image.',
            'cover_photo.mimes' => 'The cover must be a JPG, PNG, or WebP image.',
            'cover_photo.max' => 'The cover image must not exceed 10 MB.',
            'cover_photo.dimensions' => 'The cover image must be at least 800 pixels wide and 300 pixels tall.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $group=$this->route('group');$owner=$group?->creator;if(!$owner)return;
            $map=['downloads_enabled'=>'download_controls','favourites_enabled'=>'client_favourites','watermark_enabled'=>'watermark','face_recognition_enabled'=>'find_my_photos'];
            foreach($map as$field=>$feature)if($this->has($field)&&$this->boolean($field)&&!$group->{$field}&&!app(\App\Services\Billing\AccountEntitlements::class)->allows($owner,$feature))$validator->errors()->add($field,'Your current plan does not include this feature.');
        });
    }
}
