<?php

namespace Tests\Unit\Api;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ap_i用の_index_contact_requestのキーワードフィルタが有効で、不正な値を拒否する(): void
    {
        $category = Category::factory()->create();

        $validData = [
            'keyword' => 'テスト',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-08-10',
            'per_page' => 15,
        ];

        $request = new IndexContactRequest;
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = [
            'gender' => 99,
            'category_id' => 99,
            'date' => 'invalid-date',
            'per_page' => 'not-a-number',
        ];

        $validatorInvalid = Validator::make($invalidData, $request->rules());
        $this->assertTrue($validatorInvalid->fails());
        $this->assertTrue($validatorInvalid->errors()->has('gender'));
        $this->assertTrue($validatorInvalid->errors()->has('category_id'));
        $this->assertTrue($validatorInvalid->errors()->has('date'));
        $this->assertTrue($validatorInvalid->errors()->has('per_page'));
    }

    /** @test */
    public function ap_i用の_store_contact_requestの全必須項目・タグ入力を受け付け、不正な値を拒否する(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validData = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $request = new StoreContactRequest;
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = [
            'first_name' => '',
            'last_name' => '',
            'gender' => 99,
            'email' => 'invalid-email',
            'tel' => 'abc-xxxx',
            'address' => '',
            'category_id' => 99999,
            'detail' => '',
            'tag_ids' => [99999],
        ];

        $validatorInvalid = Validator::make($invalidData, $request->rules());
        $this->assertTrue($validatorInvalid->fails());
        $this->assertTrue($validatorInvalid->errors()->has('first_name'));
        $this->assertTrue($validatorInvalid->errors()->has('email'));
        $this->assertTrue($validatorInvalid->errors()->has('category_id'));
    }
}
