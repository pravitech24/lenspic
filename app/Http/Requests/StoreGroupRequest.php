<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Group::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'event_type' => ['required', 'string'],
            'privacy' => ['required', 'in:public,private,link_only'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:min_width=800,min_height=400'],
            'membership_limit' => ['nullable', 'integer', 'min:2', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'access_options' => ['required', 'array', 'min:1'],
            'access_options.*' => ['required', 'in:partial_access,full_access'],
            'submission_token' => ['nullable', 'uuid'],
            'allow_guest_upload' => ['sometimes', 'boolean'],
            'face_recognition_enabled' => ['sometimes', 'boolean'],
            'watermark_enabled' => ['sometimes', 'boolean'],
            'watermark_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
