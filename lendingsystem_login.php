<?php

// セッションの開始とセッション名の設定
session_name('rental_session'); // セッション名を設定
session_start(); // セッションを開始

// ログイン状態を保持する機能
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: admin_dashboard/exsecure'); // 既にログインしている場合、ダッシュボードにリダイレクト
    exit;
}

// データベース接続情報をインクルード
include('db_connection.php');

$error = '';

// POSTリクエストが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Turnstileのレスポンスが存在するか確認
    if (!isset($_POST['cf-turnstile-response'])) {
        $error = 'Turnstileのレスポンスが取得できませんでした。';
    } else {
        // Turnstileのレスポンスを取得
        $turnstile_response = $_POST['cf-turnstile-response'];
        
        // Turnstileシークレットキー
        $secret_key = "0x4AAAAAAAimZgS0CJaBibwTO-VrmvrM7JQ"; // 実際のシークレットキーを入力
        
        // Turnstile APIにリクエストを送信して検証
        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $secret_key,
            'response' => $turnstile_response,
        ];
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($verify_url, false, $context);
        $result = json_decode($response, true);
        
        // Turnstile認証が成功したかどうかを確認
        if ($result['success']) {
            // Turnstile認証が成功した場合、ユーザー認証を開始
            $userid = $_POST['username'];
            $password = $_POST['password'];

            // `users` テーブルと `user_applications` テーブルを確認
            $stmt = $anketo_conn->prepare('
                SELECT 
                    u.password, u.name, u.status, u.login_attempts, ua.can_login
                FROM 
                    users u
                LEFT JOIN 
                    user_applications ua 
                ON 
                    u.mailaddress = ua.mailaddress AND ua.application_id = 3
                WHERE 
                    u.userid = ?
            ');
            $stmt->bind_param('s', $userid);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($hashed_password, $name, $status, $login_attempts, $can_login);
                $stmt->fetch();

                // `login_attempts` が4以上なら `status` を無効化
                if ($login_attempts >= 4) {
                    $update_stmt = $anketo_conn->prepare('UPDATE users SET status = 0 WHERE userid = ?');
                    $update_stmt->bind_param('s', $userid);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $error = 'お客様のアカウントはロックされております。カスタマーデスクへお問い合わせください。';
                } elseif (!password_verify($password, $hashed_password)) {
                    // パスワードが正しくない場合、`login_attempts` を増加
                    $update_stmt = $anketo_conn->prepare('UPDATE users SET login_attempts = login_attempts + 1 WHERE userid = ?');
                    $update_stmt->bind_param('s', $userid);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $error = 'ユーザー名またはパスワードが正しくありません。';
                } elseif ($status != 1) {
                    // ユーザーのステータス確認
                    $error = 'このアカウントは無効化されています。';
                } elseif ($can_login != 1) {
                    // アプリケーションのログイン権限確認
                    $error = 'このアプリケーションにアクセスする権限がありません。';
                } else {
                    // ログイン成功処理
                    session_regenerate_id(true); // セッションIDを再生成
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $userid;
                    $_SESSION['name'] = $name;

                    // `login_attempts` をリセット
                    $reset_stmt = $anketo_conn->prepare('UPDATE users SET login_attempts = 0 WHERE userid = ?');
                    $reset_stmt->bind_param('s', $userid);
                    $reset_stmt->execute();
                    $reset_stmt->close();

                    // ダッシュボードへリダイレクト
                    header('Location: admin_dashboard/exsecure');
                    exit;
                }
            } else {
                $error = 'ユーザー名またはパスワードが正しくありません。';
            }

            $stmt->close();
        } else {
            $error = 'Turnstile認証に失敗しました。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物品ナビ管理システムログイン</title>
    <link rel="icon" href="./ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="styles/lendingsystem_login.css">
</head>
<body>
    <!-- ヘッダー追加 -->
    <header>
        <div class="header-left">
            <a href="index" class="header-link">物品ナビ管理システム</a>
        </div>
    </header>

    <div class="login-container">
        <!-- ロゴを追加 -->
        <img src="images/account1-icon.png" alt="サイトロゴ" class="login-logo">
        
        <h2>物品ナビ管理システム</h2>
        <h3>CoreAccount</h3>
        
        <!-- エラーメッセージの表示 -->
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        
        <form action="lendingsystem_login" method="post">
            <label for="username">ユーザー名</label>
            <input type="text" name="username" required>

            <label for="password">パスワード</label>
            <input type="password" name="password" required>

            <!-- Cloudflare Turnstile -->
            <div class="cf-turnstile" data-sitekey="0x4AAAAAAAimZuLzcP7hrott"></div>

            <button type="submit">ログイン</button>
        </form>

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    </div>
</body>
</html>
