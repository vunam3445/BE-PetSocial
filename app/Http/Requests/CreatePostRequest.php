<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize()
    {
        // Nếu bạn muốn chỉ cho user đã login được post
        return true;
    }

    public function rules()
    {
        return [
            'author_id'      => 'required|uuid|exists:users,user_id',
            'caption'        => 'nullable|string|max:5000',
            'visibility'     => 'nullable|in:public,private,friends',
            'shared_post_id' => 'nullable|uuid|exists:posts,post_id',
            'group_id'       => 'nullable|uuid|exists:groups,group_id',

            // Media
            'media'                  => 'nullable|array',
            'media.*.file'           => 'required_with:media|file|max:51200', // 50MB
            'media.*.media_type'     => 'required_with:media|in:image,video',
            'media.*.order'          => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('media') && is_array($this->media)) {
                $imageCount = 0;
                $videoCount = 0;

                foreach ($this->media as $item) {
                    if (isset($item['media_type']) && $item['media_type'] === 'image') {
                        $imageCount++;
                    }
                    if (isset($item['media_type']) && $item['media_type'] === 'video') {
                        $videoCount++;
                    }
                }

                if ($imageCount > 10) {
                    $validator->errors()->add('media', 'Không được upload quá 10 ảnh.');
                }

                if ($videoCount > 5) {
                    $validator->errors()->add('media', 'Không được upload quá 5 video.');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'author_id.required'       => 'Thiếu ID tác giả.',
            'author_id.exists'         => 'Tác giả không tồn tại.',
            'visibility.in'            => 'Chế độ hiển thị không hợp lệ.',
            'media.*.file.max'         => 'File không được vượt quá 50MB.',
            'media.*.media_type.in'    => 'Loại media không hợp lệ (image/video).',
        ];
    }
}
