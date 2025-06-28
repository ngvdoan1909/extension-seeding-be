<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'domain' => 'required|string',
            'commissions' => 'required|array|min:1',
            'commissions.*.key_word' => 'required|string|max:100',
            'commissions.*.url' => 'required|string|max:255|unique:commissions,url',
            'commissions.*.daily_limit' => 'required|integer',
            'commissions.*.images.*' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'name' => 'required|string|max:100',
            'domain' => 'required|string',
            'commissions' => 'sometimes|array|min:1',
            'commissions.*.key_word' => 'required|string|max:100',
            'commissions.*.url' => 'required|string|max:255|unique:commissions,url',
            'commissions.*.daily_limit' => 'required|integer',
            'commissions.*.images.*' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên trang web trang trống',
            'name.string' => 'Tên trang web phải là 1 chuỗi',
            'name.max' => 'Tên trang web có tối đa là 100 ký tự',
            'domain.required' => 'Tên miền đang trống',
            'domain.string' => 'Tên miền phải là 1 chuỗi',
            'commissions.required' => 'Phải có ít nhất một nhiệm vụ',
            'commissions.array' => 'Nhiệm vụ phải là dạng mảng',
            'commissions.min' => 'Phải có ít nhất một nhiệm vụ',
            'commissions.*.key_word.required' => 'Từ khóa tìm kiếm đang trống',
            'commissions.*.key_word.string' => 'Từ khóa tìm kiếm phải là 1 chuỗi',
            'commissions.*.key_word.max' => 'Từ khóa tìm kiếm có tối đa là 100 ký tự',
            'commissions.*.url.required' => 'Đường dẫn đang trống',
            'commissions.*.url.string' => 'Đường dẫn phải là 1 chuỗi',
            'commissions.*.url.max' => 'Đường dẫn có tối đa là 255 ký tự',
            'commissions.*.url.unique' => 'Đường dẫn này đã tồn tại',
            'commissions.*.daily_limit.required' => 'Số lượng 1 ngày đang trống',
            'commissions.*.daily_limit.integer' => 'Số lượng 1 ngày phải là 1 số',
            'commissions.*.images.*.image' => 'File phải là hình ảnh',
            'commissions.*.images.*.mimes' => 'Ảnh phải có định dạng PNG, JPG, JPEG, WEBP',
            'commissions.*.images.*.max' => 'Ảnh không được vượt quá 2MB',
        ];
    }
}
