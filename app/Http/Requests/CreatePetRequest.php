<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePetRequest extends FormRequest
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
            'type' => 'required|string|max:100',
            'breed' => 'required|string|max:100',
            'gender' => 'required|in:male,female,unknown', // hoặc: 'nam,nữ'
            'birthday' => 'required|date|before:today',
            'avatar_url' => 'nullable|url',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Pet name is required',
            'type.required' => 'Pet type is required',
            'breed.required' => 'Pet breed is required',
            'gender.required' => 'Pet gender is required',
            'birthday.required' => 'Pet birthday is required',
            'avatar_url.url' => 'Pet avatar must be a valid URL',
        ];
    }
}
