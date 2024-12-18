<?php
// セッションの開始
session_name('rental_session');
session_start();

include('../db_connection.php');


// テーブルデータを取得
$query = "SELECT id, name, type FROM department_store";
$result = $conn->query($query);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // 新しいレコードを追加
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        if (!empty($name) && !empty($type)) {
            $insertQuery = "INSERT INTO department_store (name, type) VALUES (?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ss", $name, $type);
            $stmt->execute();
            header('Location: jurisdictional_setting.php');
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        // レコードを削除
        $id = $_POST['id'] ?? 0;
        $deleteQuery = "DELETE FROM department_store WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: jurisdictional_setting.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データ管理</title>
    <link rel="icon" href="../ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="styles/admin_dashboard.css">
    <style>
        .input-container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .input-container h3 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }

        .input-container .form-group {
            margin-bottom: 15px;
        }

        .input-container .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .input-container .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .input-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .input-container button:hover {
            background-color: #0056b3;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .home-button {
            position: absolute;
            top: 10px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .home-button:hover {
            background-color: #0056b3;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .logout-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logout-button:hover {
            background-color: #c82333;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
<header>
    <div class="header-left">
        <h1>組織部門管理</h1>
    </div>
    <a href="./admin_dashboard" class="home-button">ホーム</a>
</header>
<div class="container">
    <div class="dashboard-info">
        <h2>組織部門管理</h2>

        <table class="task-info">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>タイプ</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete" class="logout-button">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="input-container">
            <h3>新しいレコードを追加</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="name">名前</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="type">タイプ</label>
                    <input type="text" id="type" name="type" required>
                </div>
                <button type="submit" name="add">追加</button>
            </form>
        </div>
    </div>
</div>
<footer>
<p>&copy; 物品ナビ｜<strong>Powerd by Synfortech</strong></p>
</footer>
</body>
</html>
