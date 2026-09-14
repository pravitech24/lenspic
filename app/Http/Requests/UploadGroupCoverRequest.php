<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadGroupCoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');
        return $group && ($this->user()?->can('update', $group) ?? false);
    }

    public function rules(): array
    {
        return ['cover_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:min_width=800,min_height=300']];
    }
}
