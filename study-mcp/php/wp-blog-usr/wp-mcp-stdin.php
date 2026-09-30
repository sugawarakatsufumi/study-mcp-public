<?php
/**
 * WordPress の記事一覧・ユーザー一覧を返す PHP MCP サーバー (STDIO版)
 */
require_once __DIR__ . '/wp-mcp-common.php';

/**
 * JSON-RPC レスポンス送信関数
 */
function sendResponse($response) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
    fflush(STDOUT);
}

// 標準入力 (stdin) から JSON-RPC リクエストを 1 行ずつ読み込むループ
while ($line = fgets(STDIN)) {
    $line = trim($line);
    if (empty($line)) continue;

    $request = json_decode($line, true);
    $response = handleMcpRequest($request);
    if ($response !== null) {
        sendResponse($response);
    }
}
