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

// セッションからユーザー名を取得
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'ゲスト';

// 検索条件の保持
$search_params = [
    'item_name' => isset($_POST['item_name']) ? $_POST['item_name'] : '',
    'department_name' => isset($_POST['department_name']) ? $_POST['department_name'] : '',
    'grade_year' => isset($_POST['grade_year']) ? $_POST['grade_year'] : '',
    'representative_name' => isset($_POST['representative_name']) ? $_POST['representative_name'] : '',
    'request_time' => isset($_POST['request_time']) ? $_POST['request_time'] : '',
];

// 承認処理
if (isset($_POST['approve'])) {
    $request_id = $_POST['request_id'];

    // lending_requests テーブルからデータを取得
    $sql = "SELECT * FROM lending_requests WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request) {
        // lending_status テーブルにデータを挿入
        $insert_sql = "INSERT INTO lending_status 
        (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_status, approval_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, '承認済み', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssissis", 
            $request['item_name'], $request['department_name'], $request['grade_year'], 
            $request['representative_name'], $request['project_name'], $request['quantity'], $request['request_time']);
        $insert_stmt->execute();

        if ($insert_stmt->affected_rows > 0) {
            // lending_requests テーブルから削除
            $delete_sql = "DELETE FROM lending_requests WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $request_id);
            $delete_stmt->execute();
            
            echo "<script>alert('承認が完了しました');</script>";
        } else {
            echo "<script>alert('承認に失敗しました');</script>";
        }
    }
}

// 否認処理
if (isset($_POST['deny'])) {
    $request_id = $_POST['request_id'];

    // lending_requests テーブルからデータを取得
    $sql = "SELECT * FROM lending_requests WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request) {
        // repudiation テーブルにデータを挿入
        $insert_sql = "INSERT INTO repudiation 
        (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssissis", 
            $request['item_name'], $request['department_name'], $request['grade_year'], 
            $request['representative_name'], $request['project_name'], $request['quantity'], $request['request_time']);
        $insert_stmt->execute();

        if ($insert_stmt->affected_rows > 0) {
            // lending_requests テーブルから削除
            $delete_sql = "DELETE FROM lending_requests WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $request_id);
            $delete_stmt->execute();

            echo "<script>alert('否認が完了しました');</script>";
        } else {
            echo "<script>alert('否認に失敗しました');</script>";
        }
    }
}
define('PAGE_TITLE', '物品ナビ　申請状況'); // このページ用のタイトル
include 'header.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請状況</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="styles/request_status.css">
</head>
<body>
    <!-- ヘッダー -->


    <!-- 検索フォーム -->
    <div class="search-container">
        <form method="post">
            <input type="text" name="item_name" placeholder="物品名" value="<?= htmlspecialchars($search_params['item_name']); ?>">
            <input type="text" name="department_name" placeholder="学科名" value="<?= htmlspecialchars($search_params['department_name']); ?>">
            <input type="number" name="grade_year" placeholder="学年" value="<?= htmlspecialchars($search_params['grade_year']); ?>">
            <input type="text" name="representative_name" placeholder="代表者名" value="<?= htmlspecialchars($search_params['representative_name']); ?>">
            <input type="date" name="request_time" placeholder="申請時間" value="<?= htmlspecialchars($search_params['request_time']); ?>">
            <button type="submit">検索</button>
        </form>
    </div>

    <!-- メインコンテンツ -->
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>物品名</th>
                    <th>学科名</th>
                    <th>学年</th>
                    <th>代表者名</th>
                    <th>企画名</th>
                    <th>数量</th>
                    <th>申請時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // 検索条件を使用してSQLクエリを作成
                $sql = "SELECT * FROM lending_requests WHERE 1=1";
                $params = [];
                $types = "";

                foreach ($search_params as $key => $value) {
                    if (!empty($value)) {
                        $sql .= " AND $key LIKE ?";
                        $params[] = "%$value%";
                        $types .= "s";
                    }
                }

                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['item_name'] . "</td>";
                        echo "<td>" . $row['department_name'] . "</td>";
                        echo "<td>" . $row['grade_year'] . "</td>";
                        echo "<td>" . $row['representative_name'] . "</td>";
                        echo "<td>" . $row['project_name'] . "</td>";
                        echo "<td>" . $row['quantity'] . "</td>";
                        echo "<td>" . $row['request_time'] . "</td>";
                        echo "<td>
                                <form method='post'>
                                    <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                                    <input type='hidden' name='item_name' value='" . htmlspecialchars($search_params['item_name']) . "'>
                                    <input type='hidden' name='department_name' value='" . htmlspecialchars($search_params['department_name']) . "'>
                                    <input type='hidden' name='grade_year' value='" . htmlspecialchars($search_params['grade_year']) . "'>
                                    <input type='hidden' name='representative_name' value='" . htmlspecialchars($search_params['representative_name']) . "'>
                                    <input type='hidden' name='request_time' value='" . htmlspecialchars($search_params['request_time']) . "'>
                                    <button class='approve-btn' type='submit' name='approve'>承認</button>
                                    <button class='deny-btn' type='submit' name='deny'>否認</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>該当する申請がありません。</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
