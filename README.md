# study-mcp-public

PHP で MCP (Model Context Protocol) サーバーを自作して学ぶための学習用リポジトリです。
外部ライブラリは使わず、JSON-RPC 2.0 の `initialize` / `tools/list` / `tools/call` を素の PHP で実装しています。

## 構成

```
php/
├── seasonal-food/          # 季節のフルーツ・野菜を返すサンプル
│   ├── mcp-stdin.php       #   STDIO 版
│   └── mcp-post.php        #   HTTP(POST) 版
└── wp-blog-usr/            # WordPress REST API から記事・ユーザーを取得するサンプル
    ├── wp-mcp-common.php   #   共通処理 (.env 読込・API 呼び出し・MCP ハンドラ)
    ├── wp-mcp-stdin.php    #   STDIO 版
    └── wp-mcp-post.php     #   HTTP(POST) 版
```

## 必要環境

- PHP 8.0 以上 (CLI)
- `wp-blog-usr` を使う場合: REST API が有効な WordPress サイトと Application Password

## サンプル 1: seasonal-food

季節 (`はる` / `なつ` / `あき` / `ふゆ`、または `春` / `夏` / `秋` / `冬`) を渡すと、旬のフルーツ・野菜を返します。

| ツール | 説明 |
| --- | --- |
| `get_seasonal_fruit` | 指定した季節のおすすめフルーツ |
| `get_seasonal_vegetable` | 指定した季節のおすすめ野菜 |

### 動作確認

```bash
printf '%s\n' \
  '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}' \
  '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"get_seasonal_fruit","arguments":{"season":"秋"}}}' \
  | php php/seasonal-food/mcp-stdin.php
```

### Claude Code に登録する

```bash
claude mcp add seasonal-food -- php /絶対パス/php/seasonal-food/mcp-stdin.php
```

## サンプル 2: wp-blog-usr

WordPress の REST API を呼び出し、記事一覧・ユーザー一覧を返します。

| ツール | 説明 |
| --- | --- |
| `get_posts` | 記事一覧 (最大 20 件: id / title / link / date) |
| `get_users` | ユーザー一覧 (最大 20 件: id / name / slug) |

### 設定

`wp-blog-usr/` に `.env` を作成します (`.env.example` をコピー)。

```bash
cp php/wp-blog-usr/.env.example php/wp-blog-usr/.env
```

```
WP_URL=https://example.com
WP_USER=your-wp-username
WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx
```

Application Password は WordPress 管理画面の「ユーザー → プロフィール → アプリケーションパスワード」で発行します。

### Claude Code に登録する

```bash
claude mcp add wp-blog-usr -- php /絶対パス/php/wp-blog-usr/wp-mcp-stdin.php
```

## セキュリティ上の注意

- `.env` は **絶対にコミットしないでください** (`.gitignore` で除外済み)。
- HTTP(POST) 版 (`wp-mcp-post.php`) は **認証を実装していません**。公開サーバーに置くと、誰でも WordPress のユーザー一覧を取得できてしまいます。学習・ローカル検証用途に限り、公開する場合は Basic 認証や IP 制限などを必ず追加してください。
- HTTP 版を Web サーバーに置く場合は、`.env` が URL から直接ダウンロードされない配置・設定にしてください。
- Application Password は最小権限のユーザーで発行し、不要になったら失効させてください。

## 既知の制限

- 学習用の最小実装のため、MCP 仕様の全機能には対応していません (対応: `initialize` / `tools/list` / `tools/call`)。
- HTTP(POST) 版は認証・セッション管理・SSE ストリーミングに対応していません (1 リクエスト 1 レスポンスのみ)。

## ライセンス

未設定です。必要に応じて `LICENSE` を追加してください。
