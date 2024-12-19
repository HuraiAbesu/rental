<?php
// 必要なファイルを読み込む（DB接続）
require 'db_connection.php';

// Turnstileのシークレットキー
$secretKey = "0x4AAAAAAAimZgS0CJaBibwTO-VrmvrM7JQ";

// POSTデータをサニタイズする関数
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

try {
    // フォームからのデータ取得
    $reporter_name = sanitize_input($_POST['reporter_name'] ?? '');
    $contact_info = sanitize_input($_POST['contact_info'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '未設定');
    $damage_location = sanitize_input($_POST['damage_location'] ?? '');
    $damage_date = sanitize_input($_POST['damage_date'] ?? '');
    $damage_details = sanitize_input($_POST['damage_details'] ?? '');

    // 必須フィールドのチェック
    if (empty($reporter_name) || empty($contact_info) || empty($damage_location) || empty($damage_date) || empty($damage_details)) {
        throw new Exception("必須フィールドが入力されていません。");
    }

    // Turnstileの検証
    $token = $_POST['cf-turnstile-response'] ?? '';
    if (empty($token)) {
        throw new Exception("Turnstileトークンがありません。");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!$result['success']) {
        throw new Exception("Turnstileの検証に失敗しました。もう一度お試しください。");
    }

    // 画像のアップロード処理
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $filename = uniqid() . "_" . basename($_FILES['image']['name']);
        $upload_path = $upload_dir . $filename;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_url = $upload_path;
        } else {
            throw new Exception("画像のアップロードに失敗しました。");
        }
    }

    // データベースへの挿入
    $sql = "INSERT INTO damage_reports (reporter_name, contact_info, department, damage_location, damage_date, damage_details, image_url, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, '未対応', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reporter_name, $contact_info, $department, $damage_location, $damage_date, $damage_details, $image_url]);

    // 成功メッセージ
    echo "物損報告を受け付けました。対応までお待ちください。";
} catch (Exception $e) {
    // エラーハンドリング
    http_response_code(400);
    echo "エラー: " . $e->getMessage();
}
