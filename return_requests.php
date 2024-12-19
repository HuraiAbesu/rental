<?php

// データベース接続ファイルをインクルード
include('db_connection.php');

// トランザクションを開始
$conn->begin_transaction();

try {
    // 部門データをデータベースから取得
    $stmt = $conn->prepare("SELECT TRIM(name) AS name, type FROM department_store ORDER BY type, name");
    $stmt->execute();
    $departments_and_stalls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // POSTデータが送信された場合（団体選択）
    $selected_department = isset($_POST['department_name']) ? trim($_POST['department_name']) : null;

    // POSTデータを受け取る（返却申請）
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['item_id'])) {
        $item_id = $_POST['item_id'];

        // lending_status テーブルからデータ取得
        $stmt = $conn->prepare("SELECT * FROM lending_status WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        if ($item) {
            // 返却履歴テーブルに挿入
            $insert_stmt = $conn->prepare("
                INSERT INTO return_history 
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
            $insert_stmt->execute();

            // lending_status から削除
            $delete_stmt = $conn->prepare("DELETE FROM lending_status WHERE id = ?");
            $delete_stmt->bind_param("i", $item_id);
            $delete_stmt->execute();

            $conn->commit();
            echo "<script>alert('返却申請が正常に送信されました。'); window.location.reload();</script>";
        } else {
            throw new Exception("指定された物品が見つかりません。");
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "');</script>";
}

// 部門が選択されていない場合の処理
if (!$selected_department) {
    echo "<p style='color: red;'>部門が選択されていません。</p>";
}

// 申請中の物品一覧を取得
$lending_requests = [];
if ($selected_department) {
    $stmt = $conn->prepare("SELECT * FROM lending_requests WHERE TRIM(department_name) = ?");
    $stmt->bind_param("s", $selected_department);
    $stmt->execute();
    $lending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($lending_requests)) {
        echo "<p style='color: red;'>申請中の物品が見つかりません。</p>";
    }
}

// 承認された物品一覧を取得
$lending_status = [];
if ($selected_department) {
    $stmt = $conn->prepare("SELECT * FROM lending_status WHERE TRIM(department_name) = ?");
    $stmt->bind_param("s", $selected_department);
    $stmt->execute();
    $lending_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($lending_status)) {
        echo "<p style='color: red;'>承認された物品が見つかりません。</p>";
    }
}

// 返却履歴を取得
$return_history = [];
if ($selected_department) {
    $stmt = $conn->prepare("SELECT * FROM return_history WHERE TRIM(department_name) = ?");
    $stmt->bind_param("s", $selected_department);
    $stmt->execute();
    $return_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($return_history)) {
        echo "<p style='color: red;'>返却履歴が見つかりません。</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請状況・返却申請</title>
    <link rel="icon" href="./ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="./styles/return_requests.css">
</head>
<body>

<!-- ヘッダー -->
<header>
    <div class="header-left">
        <a href="return_requests.php">
            <h1>申請状況・返却申請</h1>
        </a>
    </div>
    <div class="header-right">
        <a href="lendingsystem_login">
            <img src="images/loginicon.png" alt="ログインアイコン" class="header-icon">ログイン
        </a>
        <a href="/">
            <img src="images/requesticon.png" alt="貸出申請アイコン" class="header-icon">貸出申請
        </a>
        <a href="./property_damage">
            <img src="./images/flag.png" alt="物損申請アイコン" class="header-icon">物損申請
        </a>
    </div>

    <!-- ハンバーガーボタン -->
    <div class="hamburger-menu-icon" id="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </div>
</header>
  
<!-- ポップアップ表示用 -->
<div id="popup-overlay" class="overlay"></div>
<div id="return-popup" class="popup">
    <p>返却された品物は、指定の保管場所に正しく戻されていることをご確認ください。返却が完了しましたら、速やかに返却申請をお願いいたします。</p>
    <button id="confirm-return">返却を申請する</button>
    <button id="cancel-return">返却申請を中止する</button>
</div>

<!-- 完了メッセージポップアップ -->
<div id="complete-popup" class="popup">
    <p>返却申請が完了しました。</p>
    <button id="close-complete">閉じる</button>
</div>

<!-- サイドメニュー -->
<aside id="sidebar-menu" class="sidebar">
    <a href="index">貸出申請</a>
    <a href="lendingsystem_login">ログイン</a>
</aside>

<!-- 学科選択画面 -->
<?php if (!$selected_department): ?>
    <div class="department-selection">
        <h2>ご自身の団体または店舗名を選択してください。</h2>
        <form method="post">
        <div class="department-buttons">
    <?php foreach ($departments_and_stalls as $department): ?>
        <?php 
            $class = ($department['type'] === '学科企画') ? 'department-option' : 'stall-option';
        ?>
        <button type="submit" name="department_name" value="<?= htmlspecialchars($department['name'], ENT_QUOTES, 'UTF-8'); ?>" class="<?= $class; ?>">
            <?= htmlspecialchars($department['name'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
    <?php endforeach; ?>
</div>

        </form>
    </div>
<?php else: ?>
    <br>
    <!-- 物品リスト表示 -->
    <div class="container">
        <h2><?= htmlspecialchars($selected_department); ?>が申請した物品一覧</h2>
        <br>

        <!-- 申請中の物品一覧 -->
        <h3>申請中の物品</h3>
        <table>
            <thead>
                <tr>
                    <th>物品名</th>
                    <th>申請時間</th>
                    <th>数量</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT * FROM lending_requests WHERE department_name = ?");
                $stmt->bind_param("s", $selected_department);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['request_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <br>
        <br>

        <!-- 承認された物品一覧 -->
        <h3>承認された物品</h3>
        <table>
            <thead>
                <tr>
                    <th>物品名</th>
                    <th>承認時間</th>
                    <th>数量</th>
                    <th>返却申請</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT * FROM lending_status WHERE department_name = ?");
                $stmt->bind_param("s", $selected_department);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['approval_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "<td>
                            <button class='return-button' data-item-id='" . htmlspecialchars($row['id']) . "'>返却申請</button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <br>
        <br>

        <!-- 返却履歴表示 -->
        <h3>返却履歴</h3>
        <table>
            <thead>
                <tr>
                    <th>物品名</th>
                    <th>申請時間</th>
                    <th>数量</th>
                    <th>返却ステータス</th>
                    <th>返却承認時間</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $return_stmt = $conn->prepare("SELECT * FROM return_history WHERE department_name = ?");
                $return_stmt->bind_param("s", $selected_department);
                $return_stmt->execute();
                $return_result = $return_stmt->get_result();

                while ($row = $return_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['return_request_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "<td class='return-status " . ($row['return_approval_status'] == '未承認' ? 'unapproved' : '') . "'>" . htmlspecialchars($row['return_approval_status']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['return_approval_time']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- JavaScript -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const returnButtons = document.querySelectorAll(".return-button");
    returnButtons.forEach(button => {
        button.addEventListener("click", function() {
            const itemId = this.getAttribute("data-item-id");
            const formData = new FormData();
            formData.append("item_id", itemId);

            fetch("", {
                method: "POST",
                body: formData
            }).then(response => {
                if (response.ok) {
                    alert("返却申請が送信されました。");
                    window.location.reload();
                } else {
                    alert("返却申請に失敗しました。");
                }
            }).catch(error => {
                alert("エラーが発生しました: " + error.message);
            });
        });
    });
});
</script>
<script src="./scripts/return_requests.js"></script>
</body>
</html>
