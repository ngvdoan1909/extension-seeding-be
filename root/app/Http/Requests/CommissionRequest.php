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
            'website_id' => 'required|uuid|exists:websites,website_id',
            'commissions' => 'required|array|min:1',
            'commissions.*.key_word' => 'required|string|max:100',
            'commissions.*.url' => 'required|string|max:255|unique:commissions,url',
            'commissions.*.daily_limit' => 'required|integer|min:0',
            'commissions.*.image' => 'nullable|array',
            'commissions.*.image.*' => 'mimes:png,jpg,jpeg,webp'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'key_word' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'daily_limit' => 'required|integer|min:0',
            'image.*' => 'nullable|mimes:png,jpg,jpeg,webp'
        ];
    }

    public function messages(): array
    {
        return [
            'website_id.required' => 'Vui lòng chọn website',
            'website_id.uuid' => 'ID website không hợp lệ',
            'website_id.exists' => 'Website không tồn tại',
            'commissions.required' => 'Phải có ít nhất một nhiệm vụ',
            'commissions.*.key_word.required' => 'Từ khóa tìm kiếm đang trống',
            'commissions.*.key_word.max' => 'Từ khóa tìm kiếm có tối đa là 100 ký tự',
            'commissions.*.url.required' => 'Đường dẫn đang trống',
            'commissions.*.url.unique' => 'Đường dẫn đã tồn tại',
            'commissions.*.daily_limit.required' => 'Số lượng 1 ngày đang trống',
            'commissions.*.daily_limit.min' => 'Số lượng không được âm',
            'commissions.*.image.*.mimes' => 'Ảnh phải có định dạng PNG, JPG, JPEG, WEBP',
        ];
    }
}