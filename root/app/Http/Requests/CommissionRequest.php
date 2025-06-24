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
        if ($this->isMethod('POST')) {
            return $this->rulesForCreate();
        } else if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->rulesForUpdate();
        }

        return [];
    }

    public function rulesForCreate(): array
    {
        return [
            'key_word' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'daily_limit' => 'required|integer',
            'image.*' => 'nullable|mimes:png,jpg,jpeg,webp'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'key_word' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'daily_limit' => 'required|integer',
            'image.*' => 'nullable|mimes:png,jpg,jpeg,webp'
        ];
    }

    public function messages(): array
    {
        return [
            'key_word.required' => 'Từ khóa tìm kiếm đang trống',
            'key_word.string' => 'Từ khóa tìm kiếm phải là 1 chuỗi',
            'key_word.max' => 'Từ khóa tìm kiếm có tối đa là 100 ký tự',
            'url.required' => 'Đường dẫn đang trống',
            'url.string' => 'Đường dẫn phải là 1 chuỗi',
            'url.max' => 'Đường dẫn có tối đa là 255 ký tự',
            'daily_limit.required' => 'Số lượng 1 ngày đang trống',
            'daily_limit.integer' => 'Số lượng 1 ngày phải là 1 số',
            'image.*.mimes' => 'Ảnh phải có định dạng PNG, JPG, JPEG, WEBP',
        ];
    }
}
