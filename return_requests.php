<?php
// セッションの開始
session_name('rental_session');
session_start();

// データベース接続ファイルをインクルード
include('db_connection.php');

// データベース接続チェック
if (!$conn || $conn->connect_error) {
    die("データベース接続に失敗しました: " . ($conn ? $conn->connect_error : "不明なエラー"));
}

// トランザクションを開始
$conn->begin_transaction();

try {
    // 部門データをデータベースから取得
    $stmt = $conn->prepare("SELECT TRIM(name) AS name, type FROM department_store ORDER BY type, name");
    $stmt->execute();
    $departments_and_stalls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // POSTデータが送信された場合（団体選択）
    $selected_department = isset($_POST['department_name']) ? trim($_POST['department_name']) : null;

    // 団体名に基づいてプレフィックスを付与
    if ($selected_department) {
        foreach ($departments_and_stalls as $department) {
            if ($selected_department === $department['name']) {
                $selected_department = ($department['type'] === '模擬店企画')
                    ? '模擬店企画/' . $selected_department
                    : '学科企画/' . $selected_department;
                break;
            }
        }

        // デバッグ用
        // echo "<p>選択された団体名（修正後）: " . htmlspecialchars($selected_department, ENT_QUOTES, 'UTF-8') . "</p>";
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['lend_item_id'])) {
        $item_id = $_POST['lend_item_id'];
    
        // lending_status からデータを取得
        $stmt = $conn->prepare("SELECT * FROM lending_status WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
    
        if ($item) {
            // lending_current に挿入
            $insert_stmt = $conn->prepare("
                INSERT INTO lending_current 
                (item_name, department_name, grade_year, representative_name, project_name, quantity, request_time, approval_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssississ",
                $item['item_name'],
                $item['department_name'],
                $item['grade_year'],
                $item['representative_name'],
                $item['project_name'],
                $item['quantity'],
                $item['request_time'],
                $item['approval_time']
            );
            $insert_stmt->execute();
    
            // lending_status から削除
            $delete_stmt = $conn->prepare("DELETE FROM lending_status WHERE id = ?");
            $delete_stmt->bind_param("i", $item_id);
            $delete_stmt->execute();
    
            $conn->commit();
            echo "<script>alert('貸し出しが正常に完了しました。'); window.location.reload();</script>";
        } else {
            throw new Exception("指定された物品が見つかりません。");
        }
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "');</script>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['return_item_id'])) {
    $item_id = $_POST['return_item_id'];

    // lending_current からデータを取得
    $stmt = $conn->prepare("SELECT * FROM lending_current WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($item) {
        // return_history に挿入
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

        // lending_current から削除
        $delete_stmt = $conn->prepare("DELETE FROM lending_current WHERE id = ?");
        $delete_stmt->bind_param("i", $item_id);
        $delete_stmt->execute();

        $conn->commit();
        echo "<script>alert('返却申請が正常に送信されました。'); window.location.reload();</script>";
    } else {
        throw new Exception("指定された物品が見つかりません。");
    }
}


// 部門が選択されていない場合の処理
if (!$selected_department) {
    echo "<p style='color: red;'>部門が選択されていません。</p>";
}

// 申請中の物品一覧を取得
$lending_requests = [];
if ($selected_department) {
    $sql = "SELECT * FROM lending_requests WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
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
    $sql = "SELECT * FROM lending_status WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_department);
    $stmt->execute();
    $lending_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($lending_status)) {
        echo "<p style='color: red;'>承認された物品が見つかりません。</p>";
    }
}

// 貸出中の物品一覧を取得
$lending_current = [];
if ($selected_department) {
    $sql = "SELECT * FROM lending_current WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_department);
    $stmt->execute();
    $lending_current = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($lending_current)) {
        echo "<p style='color: red;'>貸出中の物品が見つかりません。</p>";
    }
}

// データをグループ化する
$groupedData = [];

foreach ($lending_status as $status) {
    $key = $status['department_name'] . '_' . $status['representative_name'];
    if (!isset($groupedData[$key])) {
        $groupedData[$key] = [
            'department_name' => $status['department_name'],
            'representative_name' => $status['representative_name'],
            'items' => []
        ];
    }
    $groupedData[$key]['items'][] = [
        'item_id' => $status['id'],
        'item_name' => $status['item_name']
    ];
}

// 返却履歴を取得
$return_history = [];
if ($selected_department) {
    $sql = "SELECT * FROM return_history WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
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
    <link rel="icon" href="./ficon/rental-navi.ico" type="image/x-icon">
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

<div id="borrow-popup" class="popup">
    <p>イベントスタッフにこの画面を見せてください。</p>
    <p><strong>団体名:</strong> <span id="qr-department"></span></p>
    <p><strong>代表者名:</strong> <span id="qr-representative"></span></p>
    <div id="qr-code"></div> <!-- QRコード用 -->
    <svg id="barcode"></svg> <!-- バーコード用 -->
    <p><strong>現在の時間:</strong> <span id="current-time"></span></p>
    <p>Powered by Synfortech</p>
    <button id="close-qr-popup">閉じる</button>
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
                <?php foreach ($lending_requests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['item_name']); ?></td>
                        <td><?= htmlspecialchars($request['request_time']); ?></td>
                        <td><?= htmlspecialchars($request['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
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
            <th>貸出コード</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($lending_status as $status): ?>
            <?php
            // 現在の物品に関連するグループを抽出
            $relatedGroup = array_filter($groupedData, function ($group) use ($status) {
                return $group['department_name'] === $status['department_name'] &&
                       $group['representative_name'] === $status['representative_name'];
            });
            ?>
            <tr>
                <td><?= htmlspecialchars($status['item_name']); ?></td>
                <td><?= htmlspecialchars($status['approval_time']); ?></td>
                <td><?= htmlspecialchars($status['quantity']); ?></td>
                <td>
                <?php foreach ($lending_status as $status): ?>
    <button class="borrow-button" 
            data-item-id="<?= htmlspecialchars($status['id']); ?>" 
            data-department-name="<?= htmlspecialchars($status['department_name']); ?>" 
            data-representative-name="<?= htmlspecialchars($status['representative_name']); ?>">
        貸出コードを表示
    </button>
<?php endforeach; ?>

                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

        <br>
        <br>

        <h3>貸出中の物品</h3>
<table>
    <thead>
        <tr>
            <th>物品名</th>
            <th>貸出時間</th>
            <th>数量</th>
            <th>貸出コード</th>
            <th>返却申請</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($lending_current as $current): ?>
            <tr>
                <td><?= htmlspecialchars($current['item_name']); ?></td>
                <td><?= htmlspecialchars($current['lend_time']); ?></td>
                <td><?= htmlspecialchars($current['quantity']); ?></td>
                <td>
    <button class="borrow-button" data-item-id="123" data-department-name="模擬店A" data-representative-name="山田太郎">
        表示
    </button>
</td>

                <td>
                    <button class="return-button" data-item-id="<?= htmlspecialchars($current['id']); ?>">返却申請</button>
                </td>
            </tr>
        <?php endforeach; ?>
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
                <?php foreach ($return_history as $history): ?>
                    <tr>
                        <td><?= htmlspecialchars($history['item_name']); ?></td>
                        <td><?= htmlspecialchars($history['return_request_time']); ?></td>
                        <td><?= htmlspecialchars($history['quantity']); ?></td>
                        <td class="return-status <?= $history['return_approval_status'] == '未承認' ? 'unapproved' : ''; ?>">
                            <?= htmlspecialchars($history['return_approval_status']); ?>
                        </td>
                        <td><?= htmlspecialchars($history['return_approval_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>


<script>


document.addEventListener("DOMContentLoaded", function () {
    const borrowButtons = document.querySelectorAll(".borrow-button");
    const borrowPopup = document.getElementById("borrow-popup");
    const popupOverlay = document.getElementById("popup-overlay");
    let timeInterval; // 時間表示のインターバルID

    // 借りるボタンのクリックイベント
    borrowButtons.forEach(button => {
        button.addEventListener("click", function () {
            const itemId = this.getAttribute("data-item-id");
            const departmentName = this.getAttribute("data-department-name");
            const representativeName = this.getAttribute("data-representative-name");

            console.log("はいボタンがクリックされました");
            console.log("ItemID:", itemId);
            console.log("DepartmentName:", departmentName);
            console.log("RepresentativeName:", representativeName);

            // ポップアップにデータを設定
            document.getElementById("qr-department").textContent = departmentName;
            document.getElementById("qr-representative").textContent = representativeName;

            // QRコードとバーコードの生成
            generateQrCode(`ItemID:${itemId}`);
            generateBarcode(itemId);

            // リアルタイムで現在の時間を表示
            const currentTimeElement = document.getElementById("current-time");
            if (currentTimeElement) {
                currentTimeElement.style.color = "red"; // 赤色に設定
                clearInterval(timeInterval); // 既存のインターバルをクリア
                timeInterval = setInterval(() => {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString("ja-JP", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                        hour12: false,
                    }) + `.${now.getMilliseconds()}`;
                    currentTimeElement.textContent = timeString;
                }, 1);
            }

            // ポップアップを表示
            if (borrowPopup && popupOverlay) {
                popupOverlay.classList.add("show");
                borrowPopup.classList.add("show");
            } else {
                console.error("ポップアップ要素が見つかりません。");
            }
        });
    });

    // QRコード生成関数
    function generateQrCode(data) {
        console.log("QRコードに渡されるデータ:", data);
        const qrContainer = document.getElementById("qr-code");

        if (!qrContainer) {
            console.error("QRコードのコンテナが見つかりません");
            return;
        }

        qrContainer.innerHTML = ""; // コンテナの初期化

        try {
            const options = {
                text: data,
                width: 128,
                height: 128,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H,
            };
            new QRCode(qrContainer, options);
            console.log("QRコードが正常に生成されました");
        } catch (error) {
            console.error("QRコード生成中の例外:", error);
        }
    }

    // バーコード生成関数
    function generateBarcode(itemId) {
        console.log("バーコードに渡されるデータ:", `ItemID:${itemId}`);
        const barcodeContainer = document.getElementById("barcode");
        if (!barcodeContainer) {
            console.error("バーコードのコンテナが見つかりません");
            return;
        }

        JsBarcode(barcodeContainer, `ItemID:${itemId}`, {
            format: "CODE128",
            displayValue: true
        });
    }

    // ポップアップを閉じる処理
    const closeButton = document.getElementById("close-qr-popup");
    if (closeButton) {
        closeButton.addEventListener("click", function () {
            if (borrowPopup && popupOverlay) {
                borrowPopup.classList.remove("show");
                popupOverlay.classList.remove("show");
                clearInterval(timeInterval); // 時間表示のインターバルをクリア
            }
        });
    }
});



    </script>

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
<script src="https://cdn.jsdelivr.net/npm/easyqrcodejs@4.4.3/dist/easy.qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode/dist/JsBarcode.all.min.js"></script>

</body>
</html>
