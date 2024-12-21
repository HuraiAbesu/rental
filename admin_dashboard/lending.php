<?php
// エラー表示設定（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// セッションの開始
session_name('rental_session');
session_start();

require_once '../db_connection.php'; // データベース接続ファイル

// JSONレスポンスを必要とする場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8'); // JSONヘッダーを設定

    try {
        $action = $_POST['action'] ?? '';
        $search_term = $_POST['search_term'] ?? '';

        if ($action !== 'search') {
            http_response_code(400);
            echo json_encode(['error' => '不正なリクエストです。']);
            exit;
        }

        if (empty($search_term)) {
            http_response_code(400);
            echo json_encode(['error' => '検索条件が入力されていません。']);
            exit;
        }

        // 数字部分だけを抽出
        $normalized_term = preg_replace('/\D/', '', $search_term); // 数字以外を除去

        if (empty($normalized_term)) {
            http_response_code(400);
            echo json_encode(['error' => '有効な検索条件が見つかりません。']);
            exit;
        }

        // データベース検索
        $query = "SELECT * FROM lending_status WHERE id = ?";
        $stmt = $conn->prepare($query); // db_connection.phpで定義された$connを使用
        if (!$stmt) {
            throw new Exception('SQLステートメントの準備に失敗: ' . $conn->error);
        }
        $stmt->bind_param('i', $normalized_term); // 数字として検索
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'サーバーエラー: ' . $e->getMessage()]);
    }
    exit; // JSONレスポンスを返した後に終了
}

// 以下HTML部分（GETリクエスト）
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>貸出処理</title>
    <link rel="stylesheet" href="./styles/lending.css">
    <script>
        // 全角文字を半角文字に変換する関数
        function convertToHalfWidth(str) {
            return str.replace(/[Ａ-Ｚａ-ｚ０-９！-～]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });
        }
    </script>
</head>
<body>
    <h1>貸出処理</h1>
    <form id="searchForm">
        <input 
            type="text" 
            name="search_term" 
            id="searchInput" 
            placeholder="バーコードまたはキーワードを入力" 
            required
            oninput="this.value = convertToHalfWidth(this.value);" 
        >
        <button type="submit">検索</button>
    </form>

    <!-- 検索結果表示エリア -->
    <div id="searchResults"></div>

    <script>
        const searchForm = document.getElementById('searchForm');
        const searchResults = document.getElementById('searchResults');

        // 検索フォーム送信時の処理
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault(); // デフォルトの送信をキャンセル
            const searchInput = document.getElementById('searchInput').value;

            if (!searchInput.trim()) {
                alert('検索条件を入力してください。');
                return;
            }

            // サーバーへ非同期リクエスト
            fetch('lending.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=search&search_term=${encodeURIComponent(searchInput)}`
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error || `HTTPエラー: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.length === 0) {
                    searchResults.innerHTML = '<p>該当するデータが見つかりません。</p>';
                } else {
                    searchResults.innerHTML = data.map(item => `
                        <div>
                            <p><strong>物品名:</strong> ${item.item_name}</p>
                            <p><strong>代表者:</strong> ${item.representative_name}</p>
                            <p><strong>数量:</strong> ${item.quantity}</p>
                        </div>
                    `).join('');
                }
            })
            .catch(error => {
                searchResults.innerHTML = `<p>エラー: ${error.message}</p>`;
                console.error('デバッグ情報:', error);
            });
        });
    </script>
</body>
</html>
