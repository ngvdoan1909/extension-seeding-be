<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website_id' => 'required|uuid|exists:websites,website_id',
            'daily_limit' => 'required|integer|min:0',
            // 'urls' => 'required|array|min:1',
            // 'urls.*.url' => 'required|url|max:255',
            // 'urls.*.key_word' => 'required|string|max:100',
            // 'urls.*.images' => 'sometimes|array',
            // 'urls.*.images.*' => 'sometimes|image|mimes:png,jpg,jpeg,webp|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'website_id.required' => 'Vui lòng chọn website',
            'website_id.uuid' => 'ID website không hợp lệ',
            'website_id.exists' => 'Website không tồn tại',
            'daily_limit.required' => 'Giới hạn hàng ngày là bắt buộc',
            'daily_limit.min' => 'Giới hạn không được âm',
            // 'urls.required' => 'Phải có ít nhất một URL',
            // 'urls.*.url.required' => 'URL là bắt buộc',
            // 'urls.*.url.url' => 'URL không hợp lệ',
            // 'urls.*.key_word.required' => 'Từ khóa là bắt buộc',
            // 'urls.*.key_word.max' => 'Từ khóa tối đa 100 ký tự',
            // 'urls.*.images.*.image' => 'File phải là ảnh',
            // 'urls.*.images.*.mimes' => 'Ảnh phải có định dạng PNG, JPG, JPEG, WEBP',
            // 'urls.*.images.*.max' => 'Ảnh không được vượt quá 2MB'
        ];
    }
}