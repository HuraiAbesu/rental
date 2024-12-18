<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// データベース接続ファイルをインクルード
include('../db_connection.php'); 
// セッションの開始
session_name('rental_session');
session_start();

// ユーザーがログインしていない場合、ログインページにリダイレクト
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /rental_system/lendingsystem_login');
    exit;
}

// セッションからユーザー名を取得
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'ゲスト';

// 承認済みの返却履歴を取得するSQLクエリ
$sql = "SELECT * FROM return_history WHERE return_approval_status = '承認済み'";
$result = $conn->query($sql);

// CSVダウンロードがリクエストされた場合
if (isset($_POST['download_csv'])) {
    // ヘッダー情報を設定してブラウザにCSVファイルとして送信
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=return_history.csv');
    
    // 出力バッファを使用してファイルを作成
    $output = fopen('php://output', 'w');
    
    // CSVファイルのヘッダーを設定
    fputcsv($output, array('物品名', '申請時間', '数量', '返却ステータス', '返却承認時間'));
    
    // データベースから承認済みのデータを取得
    $query = "SELECT item_name, return_request_time, quantity, return_approval_status, return_approval_time 
              FROM return_history WHERE return_approval_status = '承認済み'";
    $result = $conn->query($query);
    
    // データベースの各行をCSVに書き込み
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}    

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返却履歴</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">

    <link rel="stylesheet" href="./styles/return_history.css">
</head>
<body>

<!-- ヘッダー -->
<header>
    <div class="header-left">
        <h1>返却履歴</h1>
    </div>
    <div class="header-right">
        <span class="welcome-message">ようこそ、<?php echo htmlspecialchars($name); ?> さん</span>
        <a href="admin_dashboard" class="home-button">
                        <img src="../images/homeicon.png" alt="ホームアイコン" class="header-icon">ホームに戻る
        </a>
    </div>
</header>
<form method="post" action="">
    <button type="submit" name="download_csv" class="csv-btn">CSVダウンロード</button>
</form>
<!-- コンテンツ部分 -->
<div class="container">
    <h2>承認済みの返却履歴一覧</h2>
    
    <table>
        <thead>
            <tr>
                <th>物品名</th>
                <th>申請時間</th>
                <th>数量</th>
                <th>返却承認時間</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // 返却履歴のデータを表示
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['return_request_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['return_approval_time']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>承認済みの返却履歴がありません。</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- JavaScript -->
<script src="scripts/return_history.js"></script>

</body>
</html>