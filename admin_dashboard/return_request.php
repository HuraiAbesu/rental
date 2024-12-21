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

// 検索条件をセッションに保存または取得
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $_SESSION['search_params'] = [
        'department_name' => $_POST['department_name'] ?? '',
        'quantity' => $_POST['quantity'] ?? '',
        'return_request_time' => $_POST['return_request_time'] ?? '',
        'return_approval_status' => $_POST['return_approval_status'] ?? ''
    ];
} elseif (isset($_SESSION['search_params'])) {
    $search_params = $_SESSION['search_params'];
} else {
    $search_params = [
        'department_name' => '',
        'quantity' => '',
        'return_request_time' => '',
        'return_approval_status' => ''
    ];
}

// 返却申請承認処理
try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_return'])) {
        $item_id = $_POST['return_id'];

        // lending_status テーブルから承認された物品を取得
        $stmt = $conn->prepare("SELECT * FROM lending_status WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();

        if ($item) {
            // 取得したデータを返却履歴テーブルに挿入
            $insert_stmt = $conn->prepare("INSERT INTO return_history 
                (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_status, approval_time, return_request_time, return_approval_status, return_approval_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), '未承認', NULL)");

            $insert_stmt->bind_param("ssississs", 
                $item['item_name'], 
                $item['department_name'], 
                $item['grade_year'], 
                $item['representative_name'], 
                $item['project_name'], 
                $item['quantity'], 
                $item['request_time'], 
                $item['approval_status'], 
                $item['approval_time']
            );

            if ($insert_stmt->execute()) {
                // lending_status から物品を削除
                $delete_stmt = $conn->prepare("DELETE FROM lending_status WHERE id = ?");
                $delete_stmt->bind_param("i", $item_id);
                if ($delete_stmt->execute()) {
                    $conn->commit();
                    echo "<script>alert('返却申請が正常に送信されました。'); window.location.reload();</script>";
                } else {
                    throw new Exception("物品削除に失敗しました: " . $conn->error);
                }
            } else {
                throw new Exception("返却履歴への挿入に失敗しました: " . $conn->error);
            }
        } else {
            throw new Exception("指定された物品が見つかりません。");
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "');</script>";
}
define('PAGE_TITLE', '物品ナビ　返却申請一覧'); // このページ用のタイトル
include 'header.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返却申請一覧</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="./styles/return_request.css">
</head>
<body>


<!-- コンテンツ表示 -->
<div class="container">
    <h2>返却申請一覧</h2>

    <!-- 検索フォーム -->
    <div class="search-container">
        <form method="post">
            <input type="text" name="department_name" placeholder="学科名/模擬店名" value="<?= htmlspecialchars($search_params['department_name']); ?>">
            <input type="number" name="quantity" placeholder="数量" value="<?= htmlspecialchars($search_params['quantity']); ?>">
            <input type="date" name="return_request_time" placeholder="返却申請時間" value="<?= htmlspecialchars($search_params['return_request_time']); ?>">
            <select name="return_approval_status">
                <option value="">すべてのステータス</option>
                <option value="未承認" <?= $search_params['return_approval_status'] == '未承認' ? 'selected' : ''; ?>>未承認</option>
                <option value="承認済み" <?= $search_params['return_approval_status'] == '承認済み' ? 'selected' : ''; ?>>承認済み</option>
            </select>
            <button type="submit" name="search">検索</button>
        </form>
    </div>

    <!-- 返却履歴表示 -->
    <table>
        <thead>
            <tr>
                <th>物品名</th>
                <th>申請者名</th>
                <th>数量</th>
                <th>返却申請時間</th>
                <th>返却承認ステータス</th>
                <th>承認操作</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // 検索条件に基づいて返却申請一覧を取得
            $sql = "SELECT * FROM return_history WHERE 1=1";
            $params = [];
            $types = '';

            if (!empty($search_params['department_name'])) {
                $sql .= " AND department_name LIKE ?";
                $params[] = '%' . $search_params['department_name'] . '%';
                $types .= 's';
            }
            if (!empty($search_params['quantity'])) {
                $sql .= " AND quantity = ?";
                $params[] = $search_params['quantity'];
                $types .= 'i';
            }
            if (!empty($search_params['return_request_time'])) {
                $sql .= " AND return_request_time = ?";
                $params[] = $search_params['return_request_time'];
                $types .= 's';
            }
            if (!empty($search_params['return_approval_status'])) {
                $sql .= " AND return_approval_status = ?";
                $params[] = $search_params['return_approval_status'];
                $types .= 's';
            }

            $stmt = $conn->prepare($sql);
            if ($types) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['item_name']); ?></td>
                    <td><?= htmlspecialchars($row['representative_name']); ?></td>
                    <td><?= htmlspecialchars($row['quantity']); ?></td>
                    <td><?= htmlspecialchars($row['return_request_time']); ?></td>
                    <td class="return-status <?= $row['return_approval_status'] == '未承認' ? 'unapproved' : 'approved'; ?>">
                        <?= htmlspecialchars($row['return_approval_status']); ?>
                    </td>
                    <td>
                        <?php if ($row['return_approval_status'] === '未承認'): ?>
                            <form method="post">
                                <input type="hidden" name="return_id" value="<?= htmlspecialchars($row['id']); ?>">
                                <button type="submit" name="approve_return" class="approve-btn">承認</button>
                            </form>
                        <?php else: ?>
                            <span>承認済み</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
