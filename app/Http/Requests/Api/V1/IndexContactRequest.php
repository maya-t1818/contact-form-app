<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
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
            'gender'      => 'nullable|int|in:1,2,3',
            'category_id' => 'nullable|int|exists:categories,id',
            'date'        => 'nullable|date',
            'page'        => 'nullable|int|min:1',
            'per_page'    => 'nullable|int|min:1|max:100',
        ];
    }
}
