<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class indexContactRequest extends FormRequest
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
            'keyword'     => 'nullable|string|max:255',
            'gender'      => 'nullable|integer|in:0,1,2,3',
            'category_id' => 'nullable|integer|exists:categories,id',
            'date'        => 'nullable|date',
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword'     => '検索キーワード',
            'gender'      => '性別',
            'category_id' => 'お問い合わせの種類',
            'date'        => '日付',
        ];
    }
}