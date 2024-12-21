<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');

// ユーザーがログインしていない場合、ログインページにリダイレクト
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: https://rental.synfortech.com/lendingsystem_login');
    exit;
}

// セッションからユーザー名を取得
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'ゲスト';


define('PAGE_TITLE', '物品ナビ　貸出履歴'); // このページ用のタイトル
include 'header.php';


// rental_systemデータベースから貸出履歴を取得
$sql = "SELECT id, item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_status, approval_time FROM lending_status";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $lending_history = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $lending_history = [];

}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lending History</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">

    <link rel="stylesheet" href="./styles/lending_history.css">
</head>
<body>

    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>物品名</th>
                    <th>学科名</th>
                    <th>学年</th>
                    <th>代表者名</th>
                    <th>企画名</th>
                    <th>数量</th>
                    <th>申請時間</th>
                    <th>承認状況</th>
                    <th>承認時間</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lending_history)) : ?>
                    <?php foreach ($lending_history as $history) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history['id']); ?></td>
                            <td><?php echo htmlspecialchars($history['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['grade_year']); ?></td>
                            <td><?php echo htmlspecialchars($history['representative_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($history['request_time']); ?></td>
                            <td><?php echo htmlspecialchars($history['approval_status']); ?></td>
                            <td><?php echo htmlspecialchars($history['approval_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="10">貸出履歴はありません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>