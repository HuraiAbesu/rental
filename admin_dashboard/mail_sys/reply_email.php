<?php
session_name('rental_session');
session_start();

// CSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("不正なリクエストです。");
    }

    // 名前付きメールアドレスからメール部分を抽出
    $recipient_email = trim($_POST['recipient_email']);
    if (preg_match('/<(.+?)>/', $recipient_email, $matches)) {
        $recipient_email = $matches[1];
    }

    // メールアドレスの検証
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        die("有効なメールアドレスではありません: " . htmlspecialchars($recipient_email));
    }

    // サニタイズ
    $reply_message = htmlspecialchars(trim($_POST['reply_message']), ENT_QUOTES, 'UTF-8');
    $original_subject = htmlspecialchars(trim($_POST['original_subject']), ENT_QUOTES, 'UTF-8');
    $subject = "Re: " . $original_subject;

    // ヘッダー設定
    $from_name = "物品ナビカスタマーサポート";
    $from_email = "user-helpdesk@rental.synfortech.com";
    $headers = "From: \"$from_name\" <$from_email>\r\n";
    $headers .= "Reply-To: \"$from_name\" <$from_email>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8";

    // メール送信
    if (mail($recipient_email, $subject, $reply_message, $headers)) {
        // 送信完了ページの表示
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="refresh" content="3;url=mail.php">
            <link rel="icon" href="../../ficon/rental.png" type="image/x-icon">
            <title>送信完了</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    margin-top: 20%;
                }
                h1 {
                    color: #00a1cc;
                }
                p {
                    font-size: 16px;
                    color: #555;
                }
            </style>
        </head>
        <body>
            <h1>送信が完了しました。</h1>
            <p>3秒後にメールページに戻ります...</p>
        </body>
        </html>
        HTML;
    } else {
        echo "メールの送信に失敗しました。";
    }
} else {
    die("不正なアクセスです。");
}
