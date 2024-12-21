<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');

// ユーザー情報の取得
$name = htmlspecialchars($_SESSION['name']);
$hour = date('H');
$greeting = $hour < 12 ? "おはようございます" : ($hour < 18 ? "こんにちは" : "こんばんは");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $delete_type = $_POST['delete_type'];

    switch ($delete_type) {
        case '申請状況削除':
            $stmt = $conn->prepare("DELETE FROM lending_requests");
            $stmt->execute();
            echo "申請状況が削除されました。";
            break;

        case '貸出履歴削除':
            $stmt = $conn->prepare("DELETE FROM lending_status");
            $stmt->execute();
            echo "貸出履歴が削除されました。";
            break;

        case '返却申請削除':
            $stmt = $conn->prepare("DELETE FROM return_requests");
            $stmt->execute();
            echo "返却申請が削除されました。";
            break;

        case '返却履歴削除':
            $stmt = $conn->prepare("DELETE FROM return_history");
            $stmt->execute();
            echo "返却履歴が削除されました。";
            break;

        case '否認履歴削除': // テーブル名を正しいものに修正
            try {
                $stmt = $conn->prepare("DELETE FROM repudiation");
                $stmt->execute();
                echo "否認履歴が削除されました。";
            } catch (mysqli_sql_exception $e) {
                if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                    echo "エラー: repudiation テーブルが存在しません。";
                } else {
                    echo "エラーが発生しました: " . $e->getMessage();
                }
            }
            break;

        case '全ての履歴を削除': // 全体削除処理でもテーブル名を修正
            $tables = ['lending_requests', 'lending_status', 'return_requests', 'return_history', 'repudiation'];
            foreach ($tables as $table) {
                try {
                    $conn->query("DELETE FROM $table");
                } catch (mysqli_sql_exception $e) {
                    if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                        echo "エラー: $table テーブルが存在しません。<br>";
                    } else {
                        echo "エラーが発生しました: " . $e->getMessage() . "<br>";
                    }
                }
            }
            echo "全ての履歴が削除されました。";
            break;

        default:
            echo "無効な削除タイプです。";
    }

    // データベース接続を閉じる
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete　Master</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">

    <link rel="stylesheet" href="./styles/deletemaster.css">
</head>
<body>

<header class="header">
    <div class="header-container">
        <h1 class="site-title"><a href="./admin_dashboard">物品ナビーデータ削除</a></h1>
        <div class="user-info">
            <p class="greeting"><?= $greeting ?>、<?= htmlspecialchars($name) ?>さん</p>
            <a href="./manual-box" class="icon-link">
                <img src="./icon/help-icon.png" alt="ヘルプアイコン" class="icon-img">
                <span>ヘルプ</span>
            </a>
            <a href="./logout" class="icon-link">
                <img src="./icon/logout-icon.png" alt="ログアウトアイコン" class="icon-img">
                <span>ログアウト</span>
            </a>
        </div>
    </div>
</header>

<!-- データ削除セクション -->
<div class="delete-section">
    <h2>データ管理</h2>
    <p>注意：以下の操作は取り消すことができません。慎重に操作してください。</p>

    <button class="delete-btn" onclick="showDeletePopup('申請状況削除')">申請状況削除</button>
    <button class="delete-btn" onclick="showDeletePopup('貸出履歴削除')">貸出履歴削除</button>
    <button class="delete-btn" onclick="showDeletePopup('返却申請削除')">返却申請削除</button>
    <button class="delete-btn" onclick="showDeletePopup('返却履歴削除')">返却履歴削除</button>
    <button class="delete-btn" onclick="showDeletePopup('否認履歴削除')">否認履歴削除</button>
    <button class="delete-btn delete-all" onclick="showDeletePopup('全ての履歴を削除')">全ての履歴を削除</button>
</div>

<!-- ポップアップオーバーレイ -->
<div id="delete-popup" class="popup-overlay hidden">
    <div class="popup-content">
        <h3>警告</h3>
        <p>この動作は取り消すことができません。とても危険な行為なので十分注意して操作してください。</p>
        <form action="deletemaster.php" method="post">
            <input type="hidden" id="delete-type" name="delete_type" value="">
            <button type="submit" class="confirm-btn">削除する</button>
            <button type="button" class="cancel-btn" onclick="closeDeletePopup()">戻る</button>
        </form>
    </div>
</div>

<!-- 完了メッセージポップアップ -->
<div id="complete-popup" class="popup-overlay hidden">
    <div class="popup-content">
        <p>削除が完了しました。</p>
        <button id="close-complete">閉じる</button>
    </div>
</div>

<script src="scripts/deletemaster.js"></script>

</body>
</html>
