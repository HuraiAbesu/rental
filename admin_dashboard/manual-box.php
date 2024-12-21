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

// セッションからユーザー名を取得（エラーを避けるためのデフォルト値を設定）
$name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'ゲスト';
define('PAGE_TITLE', '物品ナビ　Manual'); // このページ用のタイトル
include 'header.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マニュアル一覧表</title>
    <link rel="stylesheet" href="./styles/rental-itemlist.css">
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
</head>
<body>

<div class="container">
    <h2>マニュアル一覧</h2>
    <table>
        <thead>
            <tr>
                <th>マニュアル名</th>
                <th>バージョン</th>
                <th>アップロード日</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <!-- 手動でPDFリンクと情報を設定 -->
            <tr>
                <td>
                    <a href="./manuals/manual1.pdf" target="_blank">
                        ユーザーマニュアル
                    </a>
                </td>
                <td>1.0</td>
                <td>2024/06/01</td>
                <td>
                    <a href="./manuals/manual1.pdf" download>
                        <images src="./images/download.png" alt="Download" style="width: 20px; height: 20px;">
                    </a>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="./manuals/manual2.pdf" target="_blank">
                        管理者ガイド
                    </a>
                </td>
                <td>2.1</td>
                <td>2024/06/10</td>
                <td>
                    <a href="./manuals/manual2.pdf" download>
                        <images src="./images/download.png" alt="Download" style="width: 20px; height: 20px;">
                    </a>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="./manuals/manual3.pdf" target="_blank">
                        システム設定マニュアル
                    </a>
                </td>
                <td>3.0</td>
                <td>2024/06/15</td>
                <td>
                    <a href="./manuals/manual3.pdf" download>
                        <images src="./images/download.png" alt="Download" style="width: 20px; height: 20px;">
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
