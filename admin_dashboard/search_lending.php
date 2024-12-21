<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connection.php';

header('Content-Type: application/json; charset=utf-8'); // 必ずJSONを返す

if (isset($_POST['search_term'])) {
    $search_term = $_POST['search_term'];

    try {
        $query = "SELECT * FROM lending_status WHERE item_name LIKE ? OR FIND_IN_SET(?, REPLACE(item_name, '’', ','))";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$search_term%", $search_term]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results); // 正常なJSONレスポンス
    } catch (PDOException $e) {
        http_response_code(500); // サーバーエラー
        echo json_encode(['error' => 'データベースエラー: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400); // リクエストエラー
    echo json_encode(['error' => '検索条件が送信されていません。']);
}
?>
