<?php
session_start();
include('db_connection.php');

// レスポンスの初期化
$response = ["success" => false, "error" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['lend_item_id'])) {
    $item_id = $_POST['lend_item_id'];

    // トランザクション開始
    $conn->begin_transaction();

    try {
        // lending_status からデータを取得
        $stmt = $conn->prepare("SELECT * FROM lending_status WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        if (!$item) {
            throw new Exception("指定された物品が見つかりません。");
        }

        // lending_current にデータを挿入
        $insert_stmt = $conn->prepare("
            INSERT INTO lending_current 
            (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssississ",
            $item['item_name'],
            $item['department_name'],
            $item['grade_year'],
            $item['representative_name'],
            $item['project_name'],
            $item['quantity'],
            $item['request_time'],
            $item['approval_time']
        );
        if (!$insert_stmt->execute()) {
            throw new Exception("lending_current への挿入に失敗しました。");
        }

        // lending_status から削除
        $delete_stmt = $conn->prepare("DELETE FROM lending_status WHERE id = ?");
        $delete_stmt->bind_param("i", $item_id);
        if (!$delete_stmt->execute()) {
            throw new Exception("lending_status からの削除に失敗しました。");
        }

        $conn->commit();
        $response["success"] = true;
    } catch (Exception $e) {
        $conn->rollback();
        $response["error"] = $e->getMessage();
    }
}

// レスポンスを返す
header("Content-Type: application/json");
echo json_encode($response);
?>
