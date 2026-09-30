<?php
/**
 * 季節のフルーツ・野菜を返す PHP MCP サーバー (STDIO版)
 */

// エラー出力が stdout に混ざると JSON-RPC 通信が壊れるため画面表示をオフ
ini_set('display_errors', '0');
error_reporting(E_ALL);

// データ定義
$fruits = [
    'はる' => 'さくらんぼ',
    'なつ' => 'スイカ',
    'あき' => 'なし',
    'ふゆ' => 'みかん'
];

$vegetables = [
    'はる' => 'なのはな',
    'なつ' => 'きゅうり',
    'あき' => 'なす',
    'ふゆ' => '大根'
];

/**
 * 表記の揺れ（「秋」「あき」等）を吸収するための共通変換関数
 */
function normalizeSeason($season) {
    $map = [
        '春' => 'はる', 'はる' => 'はる',
        '夏' => 'なつ', 'なつ' => 'なつ',
        '秋' => 'あき', 'あき' => 'あき',
        '冬' => 'ふゆ', 'ふゆ' => 'ふゆ',
    ];
    return $map[$season] ?? $season;
}

/**
 * JSON-RPC レスポンス送信関数
 */
function sendResponse($response) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
    fflush(STDOUT);
}

// 標準入力 (stdin) から JSON-RPC リクエストを 1 行ずつ読み込むループ
while ($line = file_get_contents('php://input')) {
    $line = trim($line);
    if (empty($line)) continue;

    $request = json_decode($line, true);
    if (!$request || !isset($request['method'])) continue;

    $method = $request['method'];
    $id = $request['id'] ?? null;

    // 1. 初期化処理 (initialize)
    if ($method === 'initialize') {
        sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => new stdClass()
                ],
                'serverInfo' => [
                    'name' => 'php-seasonal-food-mcp',
                    'version' => '1.0.0'
                ]
            ]
        ]);
        continue;
    }

    // 2. 初期化完了通知 (notifications/initialized)
    if ($method === 'notifications/initialized') {
        continue;
    }

    // 3. 利用可能なツール一覧の返却 (tools/list)
    if ($method === 'tools/list') {
        sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => [
                    [
                        'name' => 'get_seasonal_fruit',
                        'description' => '指定された季節（はる、なつ、あき、ふゆ）のおすすめフルーツを返します。',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'season' => [
                                    'type' => 'string',
                                    'description' => '季節（例：はる、なつ、あき、ふゆ、春、夏、秋、冬）'
                                ]
                            ],
                            'required' => ['season']
                        ]
                    ],
                    [
                        'name' => 'get_seasonal_vegetable',
                        'description' => '指定された季節（はる、なつ、あき、ふゆ）のおすすめ野菜を返します。',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'season' => [
                                    'type' => 'string',
                                    'description' => '季節（例：はる、なつ、あき、ふゆ、春、夏、秋、冬）'
                                ]
                            ],
                            'required' => ['season']
                        ]
                    ]
                ]
            ]
        ]);
        continue;
    }

    // 4. ツール実行処理 (tools/call)
    if ($method === 'tools/call') {
        $toolName = $request['params']['name'] ?? '';
        $arguments = $request['params']['arguments'] ?? [];
        $rawSeason = $arguments['season'] ?? '';
        $season = normalizeSeason($rawSeason);

        if ($toolName === 'get_seasonal_fruit') {
            $resultText = $fruits[$season] ?? '「はる」「なつ」「あき」「ふゆ」のいずれかを指定してください。';
            sendResponse([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => $resultText]
                    ]
                ]
            ]);
        } elseif ($toolName === 'get_seasonal_vegetable') {
            $resultText = $vegetables[$season] ?? '「はる」「なつ」「あき」「ふゆ」のいずれかを指定してください。';
            sendResponse([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => $resultText]
                    ]
                ]
            ]);
        } else {
            sendResponse([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Tool not found'
                ]
            ]);
        }
        continue;
    }
}