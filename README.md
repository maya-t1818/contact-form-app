# COACHTECH お問い合わせフォーム

## 概要
確認テストを通して、教材で学んだバックエンド技術（Laravel, DB設計, テスト）を実践的にアウトプットし、復習箇所を洗い出す為に取り組みました。
- ER図を作成し、リレーションを確認。モデルとマイグレーションを作成。seederとfactoryを使いマイグレーション実行
- お問合わせフォームController作成(ContactController)(ContactRequestでバリデーション)
- 管理画面Controller作成(AdminController)(IndexContactRequest,StoreTagRequest,UpdateTagRequestでバリデーション)
- グループに設定しroute定義
- 公開API実装(Controller,Resource,api.phpを作成)PostmanでOK確認
- テスト(FeatureとUnitに分けてTest.phpを作成)全テストがpassする事を確認

## ER図
```mermaid
erDiagram
    users {
        bigintunsigned id PK "ID"
        string name "お名前"
        string email "メールアドレス (UNIQUE)"
        timestamp email_verified_at "メール確認日時"
        string password "パスワード"
        string remember_token "ログイン保持トークン"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }

    categories {
        bigintunsigned id PK "ID"
        string content "カテゴリ名"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }

    tags {
        bigint id PK "ID"
        string name "タグ名 (UNIQUE)"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }

    contacts {
        bigintunsigned id PK "ID"
        bigintunsigned category_id FK "カテゴリID"
        string first_name "姓"
        string last_name "名"
        integer gender "性別"
        string email "メールアドレス"
        string tell "電話番号"
        string address "アドレス"
        string building "建物名"
        text detail "お問い合わせ内容"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }

    contact_tag {
        bigintunsigned id PK "ID"
        bigintunsigned contact_id FK "お問い合わせID"
        bigintunsigned tag_id FK "タグID"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }

    categories ||--o{ contacts : "1対多 (1つのカテゴリに複数のお問い合わせ)"
    contacts ||--o{ contact_tag : "多対多の中間リレーション"
    tags ||--o{ contact_tag : "多対多の中間リレーション"
```


## 環境構築手順
1. **リポジトリをクローン**

    ```bash
    git clone https://github.com/coachtech-material/ExampleAnswer-ConfirmationTest-ContactForm.git
    ```

2. **.envファイルの準備**

    `.env.example` をコピーして `.env` を作成します。

    ```bash
    cp .env.example .env
    ```

    `.env` ファイル内の以下のDB接続情報を確認・設定します。`.env.example` のデフォルト値はSail向けではないため、以下のように変更してください。

    ```ini
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

3. **Composer依存パッケージのインストール**

    プロジェクトの初回セットアップ時は、`vendor` ディレクトリが存在しないため `sail` コマンドを使用できません。
    以下のDockerコマンドを実行して、コンテナ内で `composer install` を実行します。

    ```bash
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
    ```

4. **Laravel Sailの起動**

    以下のコマンドでDockerコンテナを起動します。

    ```bash
    ./vendor/bin/sail up -d
    ```

    > **エイリアスの設定（推奨）**
    >
    > 毎回 `./vendor/bin/sail` と入力するのは手間なので、エイリアスを設定すると便利です。
    >
    > ```bash
    > alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
    > ```

5. **アプリケーションキーの生成**

    ```bash
    sail artisan key:generate
    ```

6. **データベースのマイグレーションと初期データ投入**

    以下のコマンドでテーブルを作成し、ダミーデータを投入します。

    ```bash
    sail artisan migrate:fresh --seed
    ```
    このコマンドの入力後、下記のエラーが表示されることがあります。
    ```bash
       Illuminate\Database\QueryException 
      SQLSTATE[HY000] [1044] Access denied for user 'sail'@'%' to database 'contact-form-app' (Connection: mysql, SQL: select table_name as `name`,         (data_length + index_length) as `size`, table_comment as `comment`, engine as `engine`, table_collation as `collation` from information_schema.tables where table_schema = 'contact-form-app' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED') order by table_name)

      at vendor/laravel/framework/src/Illuminate/Database/Connection.php:829
        825▕                     $this->getName(), $query, $this->prepareBindings($bindings), $e
        826▕                 );
        827▕             }
        828▕ 
      ➜ 829▕             throw new QueryException(
        830▕                 $this->getName(), $query, $this->prepareBindings($bindings), $e
        831▕             );
        832▕         }
        833▕     }

      +43 vendor frames 

      44  artisan:35
          Illuminate\Foundation\Console\Kernel::handle()
    ```
    このエラーはコンテナ内にデータが残っており、エラーが生じているケースなどがあります。
    その場合は、以下のコマンドを順に実行して各コンテナを再起動して下さい。
    ```Bash
    sail down -v
    sail up -d　//コマンド実行後にSQLコンテナが立ち上がるまで時間がかかります。30秒ほどお待ちください。
    sail artisan migrate:fresh --seed
    ```
    

7. **フロントエンドのビルド**

    ```bash
    sail npm install
    sail npm install alpinejs
    sail npm run dev
    ```

    `npm run dev` は開発中は起動したままにしてください。

8. **アプリケーションへのアクセス**

    ブラウザで [http://localhost](http://localhost) にアクセスします。

## テスト実行

```bash
sail artisan test
```

カバレッジ付きで実行する場合:

```bash
sail artisan test --coverage
```


## 使用技術
- Laravel 10, MySQL 8.0, Nginx, Docker

## APIエンドポイント一覧
- GET	   /api/v1/contacts	            お問い合わせ一覧（検索・ページネーション付き）
- GET	   /api/v1/contacts/{contact}	お問い合わせ詳細（カテゴリ・タグ含む）
- POST	   /api/v1/contacts	            お問い合わせ新規作成
- PUT	   /api/v1/contacts/{contact}	お問い合わせ更新
- DELETE   /api/v1/contacts/{contact}	お問い合わせ削除

## 開発環境URL
- http://localhost 

## 作成者
- 竹内麻耶