<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connection.php';

header('Content-Type: application/json; charset=utf-8'); // 必ずJSONを返す

if (isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];

    try {
        $query = "SELECT * FROM lending_status WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $insert_query = "INSERT INTO lending_current (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_time, lend_time)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                $item['item_name'],
                $item['department_name'],
                $item['grade_year'],
                $item['representative_name'],
                $item['project_name'],
                $item['quantity'],
                $item['request_time'],
                $item['approval_time']
            ]);

            $delete_query = "DELETE FROM lending_status WHERE id = ?";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute([$item_id]);

            echo json_encode(['message' => '貸出処理が完了しました。']);
        } else {
            http_response_code(404); // データなしエラー
            echo json_encode(['error' => '該当データが見つかりません。']);
        }
    } catch (PDOException $e) {
        http_response_code(500); // サーバーエラー
        echo json_encode(['error' => 'データベースエラー: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400); // リクエストエラー
    echo json_encode(['error' => 'リクエストに必要なデータが不足しています。']);
}
?>
