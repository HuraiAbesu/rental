<?php
if (isset($_GET['id'])) {
    $email_id = intval($_GET['id']);

    // IMAPサーバー情報
    $imap_host = '{sv16088.xserver.jp:993/imap/ssl}INBOX';
    $email = 'user-helpdesk@rental.synfortech.com';
    $password = 'NpEmHDwo2CK4XrqmitmDKeufcbRw66Kz99vrQ';

    $imap_connection = @imap_open($imap_host, $email, $password);

    if (!$imap_connection) {
        die("接続失敗: " . imap_last_error());
    }

    $body = imap_fetchbody($imap_connection, $email_id, 1);
    $structure = imap_fetchstructure($imap_connection, $email_id);
    $encoding = isset($structure->encoding) ? $structure->encoding : 0;

    // 本文デコード
    function decode_email_body($body, $encoding) {
        switch ($encoding) {
            case 3: return base64_decode($body);
            case 4: return quoted_printable_decode($body);
            default: return $body;
        }
    }

    $decoded_body = decode_email_body($body, $encoding);

    imap_close($imap_connection);
    echo nl2br(htmlspecialchars($decoded_body, ENT_QUOTES, 'UTF-8'));
}
