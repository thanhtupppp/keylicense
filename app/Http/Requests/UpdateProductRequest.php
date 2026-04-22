<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                Rule::unique('products', 'slug')->ignore($this->route('product'))
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'url'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', 'in:Windows,macOS,Linux,Android,iOS,Web'],
            'offline_token_ttl_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'slug.required' => 'Mã sản phẩm là bắt buộc.',
            'slug.regex' => 'Mã sản phẩm chỉ được chứa chữ thường, số và dấu gạch ngang, không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.',
            'slug.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'logo_url.url' => 'URL logo phải là một URL hợp lệ.',
            'platforms.array' => 'Danh sách platform phải là một mảng.',
            'platforms.*.in' => 'Platform phải là một trong các giá trị: Windows, macOS, Linux, Android, iOS, Web.',
            'offline_token_ttl_hours.required' => 'Thời gian sống của offline token là bắt buộc.',
            'offline_token_ttl_hours.integer' => 'Thời gian sống của offline token phải là số nguyên.',
            'offline_token_ttl_hours.min' => 'Thời gian sống của offline token tối thiểu là 1 giờ.',
            'offline_token_ttl_hours.max' => 'Thời gian sống của offline token tối đa là 168 giờ (7 ngày).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên sản phẩm',
            'slug' => 'mã sản phẩm',
            'description' => 'mô tả',
            'logo_url' => 'URL logo',
            'platforms' => 'danh sách platform',
            'offline_token_ttl_hours' => 'thời gian sống offline token',
        ];
    }
}
