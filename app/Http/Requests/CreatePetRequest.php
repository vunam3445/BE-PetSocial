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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'breed' => 'required|string|max:100',
            'gender' => 'required|in:male,female,unknown',
            'birthday' => 'required|date|before:today',
            // Tăng lên 5MB (5120 KB)
            'avatar_url' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi bằng tiếng Việt
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên thú cưng.',
            'name.max' => 'Tên thú cưng không được quá 255 ký tự.',
            
            'type.required' => 'Vui lòng chọn loại thú cưng.',
            
            'breed.required' => 'Vui lòng nhập giống loài.',
            
            'gender.required' => 'Vui lòng chọn giới tính.',
            'gender.in' => 'Giới tính không hợp lệ.',
            
            'birthday.required' => 'Vui lòng chọn ngày sinh.',
            'birthday.date' => 'Ngày sinh không đúng định dạng.',
            'birthday.before' => 'Ngày sinh phải là một ngày trong quá khứ.',
            
            'avatar_url.required' => 'Vui lòng chọn ảnh đại diện.',
            'avatar_url.image' => 'Tệp tải lên phải là hình ảnh.',
            'avatar_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg hoặc webp.',
            'avatar_url.max' => 'Kích thước ảnh không được vượt quá 5MB.',
        ];
    }
}