<?php
// セッションの開始
session_name('rental_session');
session_start();


// ユーザー情報の取得
$name = htmlspecialchars($_SESSION['name']);
$hour = date('H');
$greeting = $hour < 12 ? "おはようございます" : ($hour < 18 ? "こんにちは" : "こんばんは");

// CSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// IMAPサーバー情報
$imap_host = '{sv16088.xserver.jp:993/imap/ssl}INBOX';
$email = 'user-helpdesk@rental.synfortech.com';
$password = 'NpEmHDwo2CK4XrqmitmDKeufcbRw66Kz99vrQ';

// IMAPでメール履歴を取得
$imap_connection = @imap_open($imap_host, $email, $password);

if (!$imap_connection) {
    die("メールサーバーへの接続に失敗しました: " . imap_last_error());
}

// メールデータ取得
$mailbox_overview = imap_search($imap_connection, 'ALL');
$emails = [];

if ($mailbox_overview) {
    rsort($mailbox_overview); // 最新のメールから順に並べ替え
    foreach ($mailbox_overview as $email_id) {
        $header = imap_headerinfo($imap_connection, $email_id);
        $emails[] = [
            'id' => $email_id,
            'from' => htmlspecialchars($header->fromaddress, ENT_QUOTES, 'UTF-8'),
            'subject' => htmlspecialchars(imap_utf8($header->subject), ENT_QUOTES, 'UTF-8'),
        ];
    }
} else {
    echo "メールが見つかりません。";
}

// IMAP接続を閉じる
imap_close($imap_connection);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物品ナビ カスタマーサポート</title>
    <link rel="stylesheet" href="mail.css">
</head>
<body>
<header class="header">
    <div class="header-container">
        <h1 class="site-title"><a href="../admin_dashboard">物品ナビ</a></h1>
        <div class="user-info">
            <p class="greeting"><?= $greeting ?>、<?= htmlspecialchars($name) ?>さん</p>
            <a href="../manual-box" class="icon-link">
                <img src="../icon/help-icon.png" alt="ヘルプアイコン" class="icon-img">
                <span>ヘルプ</span>
            </a>
            <a href="../logout" class="icon-link">
                <img src="../icon/logout-icon.png" alt="ログアウトアイコン" class="icon-img">
                <span>ログアウト</span>
            </a>
        </div>
    </div>
</header>
<main class="mail-app">
    <aside class="sidebar">
        <h2 class="sidebar-title">受信メール</h2>
        <div class="mail-list">
            <?php foreach ($emails as $email): ?>
                <div class="mail-item" 
                     data-id="<?= $email['id']; ?>" 
                     data-from="<?= $email['from']; ?>" 
                     data-subject="<?= $email['subject']; ?>">
                    <p class="mail-subject"><?= $email['subject']; ?></p>
                    <p class="mail-sender"><?= $email['from']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
    <section class="main-view">
        <div class="email-details">
            <h2 class="email-subject">件名を選択してください</h2>
            <p><strong>送信者:</strong> <span class="email-sender"></span></p>
            <div class="email-message">メール内容がここに表示されます。</div>
        </div>
        <form action="reply_email.php" method="POST" class="reply-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="recipient_email" class="reply-recipient">
            <input type="hidden" name="original_subject" class="reply-subject">
            <label for="reply-title">タイトル</label>
            <input type="text" id="reply-title" name="reply_title" placeholder="タイトルを入力してください" required>
            <textarea name="reply_message" placeholder="ここに返信メッセージを入力してください" required></textarea>
            <button type="submit" class="btn">返信する</button>
        </form>
    </section>
</main>

<script>
    // メールアイテムをクリックしたときに詳細を表示
    document.querySelectorAll('.mail-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const subject = this.getAttribute('data-subject');
            const sender = this.getAttribute('data-from');

            // 詳細を表示
            document.querySelector('.email-subject').innerText = subject;
            document.querySelector('.email-sender').innerText = sender;

            // Ajaxでメール本文を取得
            fetch(`get_email_body.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.email-message').innerHTML = data;
                    document.querySelector('.reply-recipient').value = sender;
                    document.querySelector('.reply-subject').value = subject;
                })
                .catch(err => console.error('メール本文の取得に失敗しました:', err));
        });
    });
</script>
</body>
</html>
