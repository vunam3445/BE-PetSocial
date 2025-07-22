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
            'name' => 'required|string|max:255',
            'avatar_url' => 'nullable|url',
            'cover_url' => 'nullable|url',
            'bio' => 'nullable|string|max:1000',
        ];
    }
}
