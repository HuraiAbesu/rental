<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');


// データベースから保管場所と物品名のデータを取得
$query = "
    SELECT id, item_name, storage_location, quantity, remarks 
    FROM item_locations 
    ORDER BY item_name, storage_location";
$result = $conn->query($query);

// データ保存用
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo "データ取得エラー: " . $conn->error;
}

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $delete_id = $_POST['delete_id'];
    $deleteQuery = "DELETE FROM item_locations WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// 登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $item_name = $_POST['item_name'];
    $storage_location = $_POST['storage_location'];
    $quantity = $_POST['quantity'];
    $remarks = $_POST['remarks'];
    $insertQuery = "INSERT INTO item_locations (item_name, storage_location, quantity, remarks) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssis", $item_name, $storage_location, $quantity, $remarks);
    $stmt->execute();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>保管場所一覧</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="./styles/item_locations.css">
    <style>
        /* 検索フォーム */
        .search-container {
            text-align: center;
            margin: 20px;
        }

        .search-container input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* 登録フォーム */
        .add-form {
            margin-top: 20px;
            text-align: center;
        }

        .add-form input {
            margin: 5px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        /* 削除ボタン */
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 5px 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        /* ポップアップ */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            z-index: 1000;
            text-align: center;
        }

        .popup button {
            margin: 10px;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .popup .confirm-btn {
            background-color: #28a745;
            color: white;
        }

        .popup .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
    <script>
        function confirmDelete(id) {
            document.getElementById('delete-popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('delete-id').value = id;
        }

        function closePopup() {
            document.getElementById('delete-popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</head>
<body>
<header>
    <div class="header-left">
        <h1>物品保管エリア</h1>
    </div>
    <a href="./admin_dashboard" class="home-button">ホーム</a>
</header>

<div class="search-container">
    <input type="text" placeholder="検索..." onkeyup="filterTable(this.value)">
</div>

<div class="container">
    <table id="data-table">
        <thead>
            <tr>
                <th>備品名</th>
                <th>保管場所</th>
                <th>個数</th>
                <th>備考</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['storage_location'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <button class="btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">削除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- 登録フォーム -->
    <div class="add-form">
        <form method="POST">
            <input type="text" name="item_name" placeholder="備品名" required>
            <input type="text" name="storage_location" placeholder="保管場所" required>
            <input type="number" name="quantity" placeholder="個数" required>
            <input type="text" name="remarks" placeholder="備考">
            <button type="submit" name="add" class="btn-add">登録</button>
        </form>
    </div>
</div>

<!-- 削除確認ポップアップ -->
<div class="overlay" id="overlay"></div>
<div class="popup" id="delete-popup">
    <p>本当に削除しますか？</p>
    <form method="POST">
        <input type="hidden" id="delete-id" name="delete_id">
        <button type="submit" name="delete" class="confirm-btn">削除</button>
        <button type="button" class="cancel-btn" onclick="closePopup()">キャンセル</button>
    </form>
</div>

<script>
    function filterTable(keyword) {
        const rows = document.querySelectorAll("#data-table tbody tr");
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(keyword.toLowerCase()) ? "" : "none";
        });
    }
</script>
</body>
</html>
