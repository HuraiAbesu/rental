<?php
// セッション名を設定
session_name('rental_session');
session_start();

// セッション変数を全て削除
$_SESSION = array();

// セッションクッキーも削除する
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// セッションを破壊
session_destroy();

// ログインページにリダイレクト
header("Location: ../lendingsystem_login");
exit;
?>