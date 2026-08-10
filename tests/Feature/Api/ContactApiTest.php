<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function jso_n形式の一覧が返り検索ページネーションが機能しバリデーションエラー時は422が返る(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(15)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/contacts?category_id={$category->id}&per_page=10");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $invalidResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/contacts?gender=99');

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    /** @test */
    public function j_so_n形式の詳細が返り存在しない_i_dで404エラー_jso_nが返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contact->id);

        $notFoundResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/contacts/99999');

        $notFoundResponse->assertStatus(404);
    }

    /** @test */
    public function レコードが作成され201が返りバリデーションエラー時は422が返る(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'category_id' => $category->id,
            'detail' => '新規お問い合わせテスト',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/contacts', $validData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', ['email' => 'yamada@example.com']);

        // 異常系：必須欠落で422
        $invalidResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/contacts', []);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'email', 'category_id']);
    }

    /** @test */
    public function レコードが更新され200が返り存在しない_i_dで404バリデーションエラー時は422が返る(): void
    {
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        $updateData = [
            'first_name' => '更新後の名前',
            'last_name' => $contact->last_name,
            'gender' => $contact->gender,
            'email' => $contact->email,
            'tel' => $contact->tel,
            'address' => $contact->address,
            'category_id' => $category->id,
            'detail' => $contact->detail,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/contacts/{$contact->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => '更新後の名前']);

        $notFoundResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/contacts/99999', $updateData);

        $notFoundResponse->assertStatus(404);

        $invalidData = array_merge($updateData, ['email' => 'invalid-email']);
        $invalidResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/contacts/{$contact->id}", $invalidData);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function レコードが削除され204が返り存在しない_i_dで404が返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);

        $notFoundResponse = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/contacts/99999');

        $notFoundResponse->assertStatus(404);
    }
}
