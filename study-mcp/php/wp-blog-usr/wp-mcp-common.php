<?php
/**
 * WordPress の記事一覧・ユーザー一覧を返す PHP MCP サーバー
 * stdio版・HTTP(POST)版の共通処理
 */

// エラー出力が stdout に混ざると JSON-RPC 通信が壊れるため画面表示をオフ
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * .env ファイルを読み込んで環境変数として設定する
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');

define('WP_URL', rtrim(getenv('WP_URL') ?: '', '/'));
define('WP_USER', getenv('WP_USER') ?: '');
define('WP_APP_PASSWORD', getenv('WP_APP_PASSWORD') ?: '');

/**
 * WordPress REST API を Application Password 認証で呼び出す
 */
function wpApiGet($endpoint) {
    $url = WP_URL . $endpoint;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Basic ' . base64_encode(WP_USER . ':' . WP_APP_PASSWORD) . "\r\n",
            'ignore_errors' => true,
        ]
    ]);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return ['error' => 'WordPress への接続に失敗しました。'];
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'WordPress からのレスポンスを解析できませんでした。'];
    }
    return $data;
}

/**
 * 記事一覧を取得する
 */
function getPosts() {
    $posts = wpApiGet('/wp-json/wp/v2/posts?per_page=20');
    if (isset($posts['error'])) {
        return $posts;
    }
    $result = [];
    foreach ($posts as $post) {
        $result[] = [
            'id' => $post['id'] ?? null,
            'title' => $post['title']['rendered'] ?? '',
            'link' => $post['link'] ?? '',
            'date' => $post['date'] ?? '',
        ];
    }
    return $result;
}

/**
 * ユーザー一覧を取得する
 */
function getUsers() {
    $users = wpApiGet('/wp-json/wp/v2/users?per_page=20');
    if (isset($users['error'])) {
        return $users;
    }
    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? '',
            'slug' => $user['slug'] ?? '',
        ];
    }
    return $result;
}

/**
 * ツール一覧の定義
 */
function getToolDefinitions() {
    return [
        [
            'name' => 'get_posts',
            'description' => 'WordPress サイトの記事一覧を取得します。',
            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
            ]
        ],
        [
            'name' => 'get_users',
            'description' => 'WordPress サイトのユーザー一覧を取得します。',
            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
            ]
        ],
    ];
}

/**
 * JSON-RPC リクエストを処理してレスポンス配列を返す
 * 通知（レスポンス不要）の場合は null を返す
 */
function handleMcpRequest($request) {
    if (!$request || !isset($request['method'])) {
        return null;
    }

    $method = $request['method'];
    $id = $request['id'] ?? null;

    // 1. 初期化処理 (initialize)
    if ($method === 'initialize') {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => new stdClass()
                ],
                'serverInfo' => [
                    'name' => 'php-wp-blog-usr',
                    'version' => '1.0.0'
                ]
            ]
        ];
    }

    // 2. 初期化完了通知 (notifications/initialized)
    if ($method === 'notifications/initialized') {
        return null;
    }

    // 3. 利用可能なツール一覧の返却 (tools/list)
    if ($method === 'tools/list') {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => getToolDefinitions()
            ]
        ];
    }

    // 4. ツール実行処理 (tools/call)
    if ($method === 'tools/call') {
        $toolName = $request['params']['name'] ?? '';

        if ($toolName === 'get_posts') {
            $data = getPosts();
        } elseif ($toolName === 'get_users') {
            $data = getUsers();
        } else {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Tool not found'
                ]
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [
                    ['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE)]
                ]
            ]
        ];
    }

    return null;
}
