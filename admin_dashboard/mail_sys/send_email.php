<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    $to = "admin@example.com";
    $subject = "お問い合わせ: $name";
    $body = "お名前: $name\nメールアドレス: $email\nメッセージ:\n$message";
    $headers = "From: $email";

    // メール送信
    if (mail($to, $subject, $body, $headers)) {
        // データベースに保存
        $stmt = $pdo->prepare("INSERT INTO email_history (sender_email, recipient_email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $to, $subject, $message]);
        echo "メールが送信され、履歴に保存されました。";
    } else {
        echo "メール送信に失敗しました。";
    }
}
?>
