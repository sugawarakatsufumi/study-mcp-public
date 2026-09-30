<?php
/**
 * WordPress の記事一覧・ユーザー一覧を返す PHP MCP サーバー (HTTPS/POST版)
 */
require_once __DIR__ . '/wp-mcp-common.php';

header('Content-Type: application/json');

$body = file_get_contents('php://input');
$request = json_decode(trim($body), true);
$response = handleMcpRequest($request);

if ($response !== null) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
