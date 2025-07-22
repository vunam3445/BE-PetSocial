<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePetRequest extends FormRequest
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
        'name' => 'sometimes|string|max:255',
        'type' => 'sometimes|string|max:100',
        'breed' => 'sometimes|string|max:100',
        'gender' => 'sometimes|in:male,female,unknown',
        'birthday' => 'sometimes|date|before:today',
        'avatar_url' => 'nullable|url',
        ];
    }
    public function messages(): array
    {
        return [
            'name.string' => 'Pet name must be a string',
            'type.string' => 'Pet type must be a string',
            'breed.string' => 'Pet breed must be a string',
            'gender.in' => 'Pet gender must be one of the following: male, female, unknown',
            'birthday.date' => 'Pet birthday must be a valid date',
            'birthday.before' => 'Pet birthday must be before today',
            'avatar_url.url' => 'Pet avatar URL must be a valid URL',
        ];
    
    
    }

}
