<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');


// テーブルデータを取得
$query = "SELECT id, item_name, category, quantity, created_at FROM items";
$result = $conn->query($query);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // 新しいアイテムを追加
        $item_name = $_POST['item_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        if (!empty($item_name) && !empty($category) && $quantity >= 0) {
            $insertQuery = "INSERT INTO items (item_name, category, quantity) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssi", $item_name, $category, $quantity);
            $stmt->execute();
            header('Location: item_management.php');
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        // アイテムを削除
        $id = $_POST['id'] ?? 0;
        $deleteQuery = "DELETE FROM items WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: item_management.php');
        exit;
    } elseif (isset($_POST['edit'])) {
        // アイテムを編集
        $id = $_POST['id'] ?? 0;
        $item_name = $_POST['item_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        if (!empty($item_name) && !empty($category) && $quantity >= 0) {
            $updateQuery = "UPDATE items SET item_name = ?, category = ?, quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssii", $item_name, $category, $quantity, $id);
            $stmt->execute();
            header('Location: item_management.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物品管理</title>
    <link rel="stylesheet" href="styles/admin_dashboard.css">
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">

<link rel="stylesheet" href="./styles/management_items.css">
</head>
<body>
<header>
    <div class="header-left">
        <h1>物品管理</h1>
    </div>
    <a href="./admin_dashboard" class="home-button">ホーム</a>
</header>
<div class="container">
    <h2>物品一覧</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>物品名</th>
                <th>カテゴリー</th>
                <th>数量</th>
                <th>作成日時</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><input type="text" name="item_name" value="<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                <td><input type="text" name="category" value="<?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                <td><input type="number" name="quantity" value="<?php echo htmlspecialchars($row['quantity'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="edit" class="btn btn-update">更新</button>
                    </form>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-delete">削除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="input-container">
        <h3>新しい物品を追加</h3>
        <form method="POST">
            <div class="form-group">
                <label for="item_name">物品名</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>
            <div class="form-group">
                <label for="category">カテゴリー</label>
                <input type="text" id="category" name="category" required>
            </div>
            <div class="form-group">
                <label for="quantity">数量</label>
                <input type="number" id="quantity" name="quantity" required>
            </div>
            <button type="submit" name="add" class="btn btn-add">追加</button>
        </form>
    </div>
</div>
<footer>
<p>&copy; 物品ナビ｜<strong>Powerd by Synfortech</strong></p>
</footer>
</body>
</html>
