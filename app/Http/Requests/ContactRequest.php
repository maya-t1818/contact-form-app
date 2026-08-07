<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'gender'      => 'required|int|in:1,2,3',
            'email'       => 'required|string|email|max:255',
            'tel'         => 'required|string|regex:/^[0-9]{10,11}$/',
            'address'     => 'required|string|max:255',
            'building'    => 'nullable|string|max:255',
            'detail'      => 'required|string|max:120',
            'category_id' => 'required|int|exists:categories,id',
            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'integer|exists:tags,id',
        ];  
    }
    
    public function messages(): array
    {
        return [
            'first_name' => '姓を入力してください',
            'last_name' => '名を入力してください',
            'gender' => '性別を選択してください',
            'email' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'tel' => '電話番号を入力してください',
            'address' => '住所を入力してください',
            'category_id' => 'お問い合わせの種類を選択してください',
            'detail' => 'お問い合わせ内容を入力してください',
            'detail.max:120' => 'お問い合わせ内容は120文字以内で入力してください',
        ];
    }
}
