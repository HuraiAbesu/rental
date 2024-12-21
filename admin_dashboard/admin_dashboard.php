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

// データベースから貸出申請（lending_requests）の件数を取得
$sql_lending = "SELECT COUNT(*) AS request_count FROM lending_requests";
$result_lending = $conn->query($sql_lending);
if ($result_lending) {
    $row_lending = $result_lending->fetch_assoc();
    $request_count = $row_lending['request_count'];
} else {
    $request_count = 0; // デフォルト値として0を設定
}

// return_history テーブルの未承認の件数を取得
$sql_unapproved_return = "SELECT COUNT(*) AS unapproved_return_count FROM return_history WHERE return_approval_status IS NULL OR return_approval_status != '承認済み'";
$result_unapproved_return = $conn->query($sql_unapproved_return);
if ($result_unapproved_return) {
    $row_unapproved_return = $result_unapproved_return->fetch_assoc();
    $unapproved_return_count = $row_unapproved_return['unapproved_return_count'];
} else {
    $unapproved_return_count = 0; // デフォルト値として0を設定
}

// 貸出および返却申請に未承認の件数がゼロかどうかを判定
$no_tasks = ($request_count == 0 && $unapproved_return_count == 0) ? "Good News! 新しいタスクはありません" : "";



?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ダッシュボード</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="./styles/admin_dashboard.css">
</head>
<body>
    <!-- ヘッダー -->
    <header>
        <div class="header-left">
            <h1>物品ナビ　管理者ダッシュボード</h1>
        </div>
        <div class="welcome-container">
            <p class="welcome-message">ようこそ、<?php echo htmlspecialchars($name); ?>さん</p>
            <form method="POST" action="logout.php">
                <button type="submit" name="logout" class="logout-button">
                    <img src="logout-icon.png" alt="Logout Icon" class="logout-icon">
                    ログアウト
                </button>
            </form>
        </div>
    </header>

    <!-- メインコンテンツ -->
    <div class="container">
        <div class="dashboard-info">
            <h2>ダッシュボード情報</h2>

            <?php if ($no_tasks): ?>
                <div class="good-news">
                    <img src="../images/good-news-icon.png" alt="Good News Icon" class="good-news-icon">
                    <p>Good News! 新しいタスクはありません。</p>
                </div>
            <?php else: ?>
                <div class="task-info">
                    <p><img src="../images/requesticona.png" alt="Lending Icon"> 貸出申請未承認件数: <?php echo $request_count; ?>件</p>
                    <p><img src="../images/returnicon.png" alt="Return Icon"> 返却申請未承認件数: <?php echo $unapproved_return_count; ?>件</p>
                </div>
            <?php endif; ?>
<br><br><br>
            <div class="task-info">
                <h3><img src="../images/info.png" alt="Lending Icon">  info</h3>
                    <p>NEW 新しく管理メニューがご利用いただけるようになりました。ぜひご利用ください。</p>
                    <p>物品ナビに関するお問い合わせは下記のメールへお願いします。<br>support@synfortech.com</p>
                </div>

<br><br>
            <!-- 業務メニュー -->
            <h3>業務メニュー</h3>
            <br>
            <nav class="menu-grid">
                <a href="request_status" class="menu-item">
                    <img src="./icon/sinsei.png" alt="申請状況">
                    貸出申請
                </a>
                <a href="lending_history" class="menu-item">
                    <img src="./icon/sinseirireki.png" alt="貸出申請承認履歴">
                    貸出申請承認履歴
                </a>
                <a href="lending" class="menu-item">
                    <img src="./icon/kasidasisyouninn.png" alt="貸出処理">
                    貸出処理
                </a>
                <a href="return_request" class="menu-item">
                    <img src="./icon/henkyakusinsei.png" alt="返却申請">
                    返却申請承認
                </a>
                <a href="return_history" class="menu-item">
                    <img src="./icon/henkyakurireki.png" alt="返却履歴">
                    返却履歴
                </a>
                <a href="repudiation" class="menu-item">

                    <img src="./icon/hininrireki.png" alt="否認履歴">
                    否認履歴
                </a>
                <a href="deletemaster" class="menu-item">
                    <img src="./icon/Sakuyo.png" alt="登録データ削除">
                   登録データ削除
                </a>
                <a href="./kouzityu" class="menu-item">
                    <img src="./icon/busson.png" alt="カスタマーサポート">
                   物損報告
                </a>
                <a href="./mail_sys/mail" class="menu-item">
                    <img src="./icon/kontakuto.png" alt="カスタマーサポート">
                   カスタマーサポート
                </a>
            </nav>

            <!-- 管理メニュー -->
             <br>
            <h3>管理メニュー</h3>
            <br>
            <nav class="menu-grid">
            <a href="https://setting.synfortech.com/user_dashboard" class="menu-item">
                    <img src="./icon/accountid.png" alt="アカウント管理">
                    アカウント管理
                </a>
                <a href="./jurisdictional_setting" class="menu-item">
                    <img src="./icon/sosiki.png" alt="組織部門管理">
                    組織部門管理
                </a>
                <a href="./management_items" class="menu-item">
                    <img src="./icon/buppinkanri .png" alt="物品管理">
                    物品管理
                </a>
                <a href="./item_locations" class="menu-item">
                    <img src="./icon/buppineria.png" alt="物品保管エリア">
                    物品保管エリア
                </a>
                <a href="./rental-itemlist" class="menu-item">
                    <img src="./icon/kasidasihinitiranhyou.png" alt="貸出品一覧表">
                   貸出品一覧表
                </a>
                <a href="./manual-box" class="menu-item">
                    <img src="./icon/manual.png" alt="マニュアル">
                    マニュアル
                </a>
            </nav>
        </div>
    </div>



    <div class="version-info">
    <p>Version: 3.5.0</p>
</div>

    <!-- フッター -->
    <footer>
        <p>&copy; 物品ナビ｜<strong>Powerd by Synfortech</strong></p>
    </footer>
</body>
</html>
