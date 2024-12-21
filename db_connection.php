<?php
// データベース接続情報
$servername = 'localhost'; // ここで定義された変数名を使用

// shopデータベースの接続情報
$shop_dbname = 'xs387434_shop';
$shop_username = 'xs387434_bw4rgfv'; // shop用のユーザーネーム
$shop_password = 'RLsKQ6CecBu3ZWqT'; // shop用のパスワード

// anketoデータベースの接続情報
$anketo_dbname = 'xs387434_anketo';
$anketo_username = 'xs387434_a4f0bfe'; // anketo用のユーザーネーム
$anketo_password = '6ZMgaXFM7TFVmJqY'; // anketo用のパスワード

// rental_systemデータベースの接続情報
$dbname = 'xs387434_rentalsystem';
$username = 'xs387434_4jf9dwf'; // rental_system用のユーザーネーム
$password = 'fADhsCLT7Q2Bqpm4'; // rental_system用のパスワード

// shopデータベース接続
$shop_conn = new mysqli($servername, $shop_username, $shop_password, $shop_dbname); // $servernameを使用
if ($shop_conn->connect_error) {
    die("shopデータベース接続失敗: " . $shop_conn->connect_error);
}

// anketoデータベース接続
$anketo_conn = new mysqli($servername, $anketo_username, $anketo_password, $anketo_dbname); // $servernameを使用
if ($anketo_conn->connect_error) {
    die("anketoデータベース接続失敗: " . $anketo_conn->connect_error);
}

// rental_systemデータベース接続
$conn = new mysqli($servername, $username, $password, $dbname); // $servernameを使用
if ($conn->connect_error) {
    die("rental_systemデータベース接続失敗: " . $conn->connect_error);
}
?>
