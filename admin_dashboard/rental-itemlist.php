<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');


// データベースから必要な情報を取得
$query = "
    SELECT DISTINCT 
        department_name, 
        project_name, 
        representative_name, 
        approval_time, 
        item_name 
    FROM lending_status
    WHERE approval_status = 'approved'
    ORDER BY approval_time DESC";
$result = $conn->query($query);

// データを保存
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo "データ取得エラー: " . $conn->error;
}
define('PAGE_TITLE', '物品ナビ　貸出品一覧'); // このページ用のタイトル
include 'header.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>貸出品一覧表</title>
<link rel="stylesheet" href="./styles/rental-itemlist.css">
<link rel="icon" href="../ficon/rental.png" type="image/x-icon">
</head>
<body>


<div class="container">
    <h2>承認済みの貸出品</h2>
    <table>
        <thead>
            <tr>
                <th>学科名</th>
                <th>企画名</th>
                <th>代表者名</th>
                <th>承認日</th>
                <th>物品名</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['project_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['representative_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['approval_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">データがありません</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
