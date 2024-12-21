<?php
// ユーザー情報の取得
$name = htmlspecialchars($_SESSION['name']);
$hour = date('H');
$greeting = $hour < 12 ? "おはようございます" : ($hour < 18 ? "こんにちは" : "こんばんは");

// ページタイトルを定義
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', '物品ナビ');
}
?>
<header class="header">
    <div class="header-container">
        <h1 class="site-title"><a href="./admin_dashboard"><?= htmlspecialchars(PAGE_TITLE) ?></a></h1>
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



<style>

/* ヘッダー */

/* リセット */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    font-family: 'Arial', sans-serif;
    width: 100%;
    height: 100%;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.header {
    background: linear-gradient(to right, #0077b6, #00a1cc);
    color: white;
    padding: 15px 20px;
    height: 50px; /* 高さを固定 */
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.header-container {
    display: flex;
    justify-content: space-between;
    width: 100%;
}

.site-title a {
    text-decoration: none;
    color: white;
    font-size: 24px;
}


.greeting {
    margin-right: 20px;
    font-size: 16px;
}
/* ユーザー情報全体を調整 */
.user-info {
    display: flex;
    align-items: center;
    justify-content: flex-end; /* 要素を右端に揃える */
    gap: 10px; /* 要素間の間隔を追加 */
}

/* ログアウトボタンのスタイル調整 */
.icon-link {
    text-decoration: none;
    color: white;
    display: flex;
    align-items: center;
    padding: 5px 10px; /* 余白を追加して見切れを防ぐ */
    white-space: nowrap; /* 文字が折り返されないようにする */
}

.icon-img {
    width: 24px;
    height: 24px;
    margin-right: 5px;
}

.icon-link:last-child { /* 最後のアイコン（ログアウト）をターゲットに */
    margin-left: 10px; /* 左の余白を減らす */
    margin-right: 0; /* 右の余白をなくす */
}

</style>
