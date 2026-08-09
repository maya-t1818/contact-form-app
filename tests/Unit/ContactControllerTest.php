<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function エクスポート正しいフィルタ条件を受け付け不正な性別や存在しないカテゴリ_i_dを拒否する(): void
    {
        $category = Category::factory()->create();

        Contact::factory()->create([
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'category_id' => $category->id,
        ]);

        $validParams = [
            'keyword' => 'テスト',
            'gender' => 1,
            'category_id' => $category->id,
        ];

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.export', $validParams));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $invalidParams = [
            'gender' => 99,
            'category_id' => 99,
        ];

        $invalidResponse = $this->get(route('admin.export', $invalidParams));
        $invalidResponse->assertSessionHasErrors(['gender', 'category_id']);
    }

    /** @test */
    public function 問い合わせ一覧が有効であり不正な性別値を拒否する()
    {
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'category_id' => $category->id,
            'created_at' => '2026-08-09 17:00:00',
        ]);

        $validParams = [
            'keyword' => 'テスト',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-09',
        ];

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.index', $validParams));
        $response->assertStatus(200);

        $invalidParams = [
            'gender' => 99,
        ];

        $invalidResponse = $this->get(route('admin.index', $invalidParams));
        $invalidResponse->assertSessionHasErrors(['gender']);
    }

    /** @test */
    public function 必須項目とタグ入力を受け付け不正な電話番号形式は拒否する()
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $invalidData = [
            'first_name' => 'テスト',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => 'invalid-phone-number',
            'address' => '大阪市',
            'detail' => '詳細',
            'category_id' => $category->id,
        ];

        $invalidResponse = $this->post(route('contact.confirm'), $invalidData);
        $invalidResponse->assertSessionHasErrors(['tel']);

        $validInputData = array_merge($invalidData, [
            'tel' => '05087654321',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]);

        $response = $this->withSession(['contact_input' => $validInputData])
            ->post(route('contact.store'));

        $response->assertRedirect(route('contact.thanks'));
        $this->assertDatabaseHas('contacts', [
            'email' => 'hanako@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where('email', 'hanako@example.com')->first();
        $this->assertCount(2, $contact->tags);
    }

    /** @test */
    public function タグ名の必須入力、文字数制限、一意性（重複禁止）が維持されている()
    {
        $user = User::factory()->create();

        Tag::factory()->create(['name' => '既存タグ']);

        $responseRequired = $this->actingAs($user)->post('/admin/tags', ['name' => '']);
        $responseRequired->assertSessionHasErrors('name');

        $responseMax = $this->actingAs($user)->post('/admin/tags', ['name' => str_repeat('a', 256)]);
        $responseMax->assertSessionHasErrors('name');

        $responseUnique = $this->post('/admin/tags', ['name' => '既存タグ']);
        $responseUnique->assertSessionHasErrors('name');
    }

    /** @test */
    public function 他で既に使用されているタグ名への変更は拒否する()
    {
        $user = User::factory()->create();
        $existingTag = Tag::factory()->create(['name' => '既存タグ']);
        $newTag = Tag::factory()->create(['name' => '新タグ']);

        $responseSelfUpdate = $this->put("/admin/tags/{$newTag->id}", ['name' => '新タグ']);
        $responseSelfUpdate->assertSessionHasNoErrors();

        $responseRequired = $this->actingAs($user)->put("/admin/tags/{$newTag->id}", ['name' => '']);
        $responseRequired->assertSessionHasErrors('name');

        $responseDuplicateUpdate = $this->actingAs($user)->put("/admin/tags/{$newTag->id}", ['name' => '既存タグ']);
        $responseDuplicateUpdate->assertSessionHasErrors('name');
    }

    /** @test */
    public function 一つのカテゴリから、紐づく複数のお問い合わせが正しく取得できる()
    {
        $category = Category::factory()->create();
        Contact::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }

    /** @test */
    public function 一つのお問い合わせが特定のカテゴリに属し、複数のタグと同期できる()
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);
        $tags = Tag::factory()->count(3)->create();

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertEquals($category->id, $contact->category->id);

        $contact->tags()->sync([$tags[0]->id, $tags[1]->id]);

        $this->assertCount(2, $contact->fresh()->tags);
        $this->assertTrue($contact->fresh()->tags->contains($tags[0]));
        $this->assertTrue($contact->fresh()->tags->contains($tags[1]));
    }

    /** @test */
    public function 中間テーブルを介して、1つのタグが複数のお問い合わせに紐づいている()
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();

        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag->id);
        }

        $this->assertCount(2, $tag->fresh()->contacts);
        $this->assertInstanceOf(Contact::class, $tag->fresh()->contacts->first());
    }
}
