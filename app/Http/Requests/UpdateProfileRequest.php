<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
   public function rules(): array
    {
        return [
            'name' => 'string|max:255',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cover_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:40096',
            'bio' => 'nullable|string|max:1000',
        ];
    }
}
