<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問合せ入力ページとthanksページが正常に表示される()
    {
        $category = Category::factory()->create(['content' => 'お問い合わせカテゴリ']);
        $tag = Tag::factory()->create(['name' => '重要タグ']);

        $response = $this->get('/contacts');
        $response->assertStatus(200);
        $response->assertViewHasAll(['categories', 'tags']);
        $response->assertSee('お問い合わせカテゴリ');
        $response->assertSee('重要タグ');

        $thanksResponse = $this->get('/contacts/thanks');
        $thanksResponse->assertStatus(200);
    }

    /** @test */
    public function 認証されたユーザーのみが管理ダッシュボードを表示できる()
    {
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect('/login');

        $user = User::factory()->create();
        $authResponse = $this->actingAs($user)->get('/admin');
        $authResponse->assertStatus(200);
    }

    /** @test */
    public function お問い合わせ確認ページが表示され、入力内容が画面に表示される()
    {
        $category = Category::factory()->create(['content' => '商品のお届けについて']);

        $validData = [
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '05012345678',
            'address' => '名古屋市',
            'detail' => '本文',
            'category_id' => $category->id,
        ];

        $response = $this->post('/contacts/confirm', $validData);
        $response->assertStatus(200);
        $response->assertSee('テスト 太郎');
        $response->assertSee('男性');
        $response->assertSee('taro@example.com');
        $response->assertSee('05012345678');
        $response->assertSee('名古屋市');
        $response->assertSee('本文');
        $response->assertSee('商品のお届けについて');

        $invalidResponse = $this->post('/contacts/confirm', []);

        $invalidResponse->assertRedirect('/');

        $invalidResponse->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'detail',
            'category_id']);
    }

    /** @test */
    public function テーブルにレコードが保存され、thanksページへリダイレクトされる()
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validInputData = [
            'first_name' => 'テスト',
            'last_name' => '花子',
            'email' => 'hanako@example.com',
            'gender' => 2,
            'tel' => '05087654321',
            'address' => '大阪市',
            'detail' => '詳細',
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->withSession(['contact_input' => $validInputData])
            ->post('/contacts');

        $response->assertRedirect('contacts/thanks');

        $this->assertDatabaseHas('contacts', ['email' => 'hanako@example.com']);

        $contact = Contact::where('email', 'hanako@example.com')->first();
        $this->assertCount(2, $contact->tags);
    }

    /** @test */
    public function 管理画面でキーワード・性別・カテゴリ・日付フィルタが機能し、結果が7件ごとにページネーションされる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->count(8)->create([
            'category_id' => $category->id,
            'gender' => 1,
        ]);

        $response = $this->actingAs($user)->get('/admin', [
            'category_id' => $category->id,
            'gender' => 1,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(7, $response->viewData('contacts')->count());
    }

    /** @test */
    public function 指定したお問い合わせがカテゴリ情報付きで詳細ページに表示される()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => 'サポート']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');
        $response->assertSee('サポート');
    }

    /** @test */
    public function レコードが正常に削除され、ログイン画面にリダイレクトされる()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.contacts.destroy', $contact->id));
        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function タグ_cru_d機能が正常に動作し、未認証ユーザーはログイン画面にリダイレクトされる()
    {
        $tag = Tag::factory()->create(['name' => '初期タグ']);

        $this->post('/admin/tags', ['name' => '新タグ'])->assertRedirect('/login');
        $this->put("/admin/tags/{$tag->id}", ['name' => '更新タグ'])->assertRedirect('/login');
        $this->delete("/admin/tags/{$tag->id}")->assertRedirect('/login');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/tags', ['name' => '新規作成タグ'])->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新規作成タグ']);

        $this->actingAs($user)->put("/admin/tags/{$tag->id}", ['name' => '変更後タグ'])->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '変更後タグ']);

        $this->actingAs($user)->delete("/admin/tags/{$tag->id}")->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function ログイン済み管理者が条件付きで_cs_vを_d_lでき、無指定時は新着順で出力される()
    {
        $user = User::factory()->create();

        Contact::factory()->create([
            'last_name' => '古いデータ',
            'created_at' => now()->subDay(),
        ]);
        Contact::factory()->create([
            'last_name' => '新しいデータ',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
