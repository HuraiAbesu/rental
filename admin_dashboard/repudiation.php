<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');

// ユーザーがログインしていない場合、ログインページにリダイレクト
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /rental_system/lendingsystem_login');
    exit;
}

// セッションからユーザー名を取得
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'ゲスト';

// データベースからrepudiationテーブルのデータを取得
$query = "SELECT * FROM repudiation";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>否認済みの申請</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">

    <link rel="stylesheet" href="styles/repudiation.css">
</head>
<body>

<!-- ヘッダー -->
<header>
    <div class="header-left">
        <h1>否認された申請一覧</h1>
    </div>
    <div class="header-right">
        <div class="welcome-message">ようこそ, <?= htmlspecialchars($name); ?>さん</div>
        <a href="admin_dashboard" class="home-button">
            <img src="../images/homeicon.png" alt="ホーム" class="header-icon"> ホームに戻る
        </a>
    </div>
</header>

<div class="container">
    <h2>否認された申請のリスト</h2>
    <table>
        <thead>
            <tr>
                <th>物品名</th>
                <th>学科名</th>
                <th>代表者名</th>
                <th>プロジェクト名</th>
                <th>数量</th>
                <th>申請時間</th>
                <th>否認時間</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['item_name']); ?></td>
                        <td><?= htmlspecialchars($row['department_name']); ?></td>
                        <td><?= htmlspecialchars($row['representative_name']); ?></td>
                        <td><?= htmlspecialchars($row['project_name']); ?></td>
                        <td><?= htmlspecialchars($row['quantity']); ?></td>
                        <td><?= htmlspecialchars($row['request_time']); ?></td>
                        <td><?= htmlspecialchars($row['denied_time']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">否認された申請はありません。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>