<?php
// データベース接続ファイルをインクルード
include('db_connection.php');

// バッファリングを開始
ob_start();

// POSTデータを受け取る
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $items = isset($_POST['rental_items']) ? $_POST['rental_items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $department_name = isset($_POST['department_name']) ? $_POST['department_name'] : '';
    $grade_year = isset($_POST['grade_year']) ? $_POST['grade_year'] : '';
    $representative_name = isset($_POST['representative_name']) ? $_POST['representative_name'] : '';
    $project_name = isset($_POST['project_name']) ? $_POST['project_name'] : '';

    if (!empty($items) && !empty($department_name) && !empty($grade_year) && !empty($representative_name) && !empty($project_name)) {
        $conn->begin_transaction();
        try {
            // SQLステートメントの準備
            $stmt = $conn->prepare("INSERT INTO lending_requests (item_name, department_name, grade_year, representative_name, project_name, quantity) 
                                    VALUES (?, ?, ?, ?, ?, ?)");

            // 各物品に対して1回だけ登録
            foreach ($items as $item_name) {
                $quantity = isset($quantities[$item_name]) ? $quantities[$item_name] : 1;
                $stmt->bind_param("ssissi", $item_name, $department_name, $grade_year, $representative_name, $project_name, $quantity);
                if (!$stmt->execute()) {
                    throw new Exception("SQLエラーが発生しました: " . $stmt->error);
                }
            }

            // トランザクションのコミット
            $conn->commit();

            // 登録成功後にリダイレクト
            header('Location: completion.php');
            exit();
        } catch (Exception $e) {
            // エラー発生時のロールバックとリダイレクト
            $conn->rollback();
            header('Location: oops.php');
            exit();
        }

        // ステートメントのクローズ
        $stmt->close();
    } else {
        echo "すべての項目を入力してください。";
    }
}

// バッファリングを終了して出力
ob_end_flush();

// 物品データを取得するためのクエリ
$sql = "SELECT item_name, category FROM items";
$result = $conn->query($sql);
$items = [
    '学外レンタル品' => [],
    '学内レンタル品' => []
];

// データベースから物品データを取得して $items 配列に格納
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = $row['category'];
        $item_name = $row['item_name'];
        if (array_key_exists($category, $items)) {
            $items[$category][] = $item_name;
        }
    }
}

// 学科と模擬店舗データを取得するクエリ
$dept_sql = "SELECT name, type FROM department_store";
$dept_result = $conn->query($dept_sql);
$departments = [];
if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = [
            'name' => $row['name'],
            'type' => $row['type']
        ];
    }
}

// データベース接続のクローズ
$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>物品ナビ-貸出申請</title>
    <link rel="icon" href="./ficon/rental.png" type="image/x-icon">
    <link rel="stylesheet" href="styles/loan_application.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <div class="header-left">
        <h1>物品ナビ-貸出申請フォーム</h1>
    </div>
    <div class="header-right">
        <a href="lendingsystem_login">
            <img src="images/loginicon.png" alt="ログインアイコン" class="header-icon">ログイン
        </a>
        <a href="return_requests">
            <img src="images/sinsei.png" alt="申請状況アイコン" class="header-icon">申請状況・返却申請
        </a>
        <a href="./property_damage">
            <img src="./images/flag.png" alt="物損報告アイコン" class="header-icon">物損報告
        </a>
    </div>
    <div class="openbtn" onclick="toggleMenu()">
        <div class="openbtn-area">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="hamburger-menu" id="sideMenu">
        <a href="lendingsystem_login">ログイン</a>
        <a href="return_requests">申請状況</a>
        <a href="./property_damage">物損報告</a>
    </div>
</header>

<div class="container">
    <form action="index.php" method="POST" id="loanForm">
        <!-- ステップ1 -->
        <div class="form-step active">
            <div class="form-group">
                <label for="department_name">ご自身の学科企画または模擬店舗名を選択してください。</label>
                <select name="department_name" id="department_name" required>
                    <option value="">選択してください</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['type'] . '/' . $dept['name']); ?>">
                            <?= htmlspecialchars($dept['type'] . '/' . $dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="grade_year">学年を入力してください。<br><div class="redtext"> ※必ず半角英数字でご入力ください。</div></label>
                <input type="number" name="grade_year" id="grade_year" min="1" max="4" required>
            </div>
            <div class="form-group">
                <label for="representative_name">学科/模擬代表者名を入力してください。</label>
                <input type="text" name="representative_name" id="representative_name" required>
            </div>
            <div class="form-group">
                <label for="project_name"></label>
                <input type="hidden" name="project_name" id="project_name">
                </div>
            <div class="button-container">
                <button type="button" class="next-btn" onclick="nextStep()">次へ</button>
            </div>
        </div>

        <!-- ステップ2 -->
        <div class="form-step hidden">
            <label>物品名:</label>
            <div class="checkbox-group">
                <?php if (!empty($items['学外レンタル品'])): ?>
                    <h3>学外レンタル品</h3>
                    <div class="category-group">
                        <?php foreach ($items['学外レンタル品'] as $item): ?>
                            <div class="item-row">
                                <label>
                                    <input type="checkbox" name="rental_items[]" value="<?= htmlspecialchars($item); ?>" onchange="updateQuantityField(this)">
                                    <?= htmlspecialchars($item); ?>
                                </label>
                                <div class="quantity-controls" style="display: none;">
                                    <button type="button" onclick="decreaseQuantity('<?= htmlspecialchars($item); ?>')">-</button>
                                    <input type="number" name="quantities[<?= htmlspecialchars($item); ?>]" id="quantity_<?= htmlspecialchars($item); ?>" value="1" min="1">
                                    <button type="button" onclick="increaseQuantity('<?= htmlspecialchars($item); ?>')">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($items['学内レンタル品'])): ?>
                    <h3>学内レンタル品</h3>
                    <div class="category-group">
                        <?php foreach ($items['学内レンタル品'] as $item): ?>
                            <div class="item-row">
                                <label>
                                    <input type="checkbox" name="rental_items[]" value="<?= htmlspecialchars($item); ?>" onchange="updateQuantityField(this)">
                                    <?= htmlspecialchars($item); ?>
                                </label>
                                <div class="quantity-controls" style="display: none;">
                                    <button type="button" onclick="decreaseQuantity('<?= htmlspecialchars($item); ?>')">-</button>
                                    <input type="number" name="quantities[<?= htmlspecialchars($item); ?>]" id="quantity_<?= htmlspecialchars($item); ?>" value="1" min="1">
                                    <button type="button" onclick="increaseQuantity('<?= htmlspecialchars($item); ?>')">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="button-container">
                <button type="button" class="next-btn" onclick="showConfirmPopup()">申請確認</button>
                <button type="button" class="prev-btn" onclick="prevStep()">戻る</button>
            </div>
        </div>
    </form>
</div>

<!-- エラーポップアップ -->
<div id="errorPopup" class="modal-overlay hidden">
    <div class="modal">
        <p>エラー！<br>全ての項目を入力してください。</p>
        <button onclick="closeErrorPopup()">閉じる</button>
    </div>
</div>

<!-- 確認ポップアップ -->
<div id="confirmPopup" class="modal-overlay hidden">
    <div class="modal">
        <h3>以下の内容で申請します。よろしいですか？</h3>
        <table id="confirmationTable">
            <tr><td>物品名:</td><td id="confirm_item_name"></td></tr>
            <tr><td>学科名:</td><td id="confirm_department_name"></td></tr>
            <tr><td>学年:</td><td id="confirm_grade_year"></td></tr>
            <tr><td>代表者名:</td><td id="confirm_representative_name"></td></tr>
            <tr><td>企画名:</td><td id="confirm_project_name"></td></tr>
            <tr><td>数量:</td><td id="confirm_quantity"></td></tr>
        </table>
        <button type="button" id="submitApplication">申請を確定</button>
        <button onclick="closeConfirmPopup()">キャンセル</button>
    </div>
</div>

<!-- ローディングポップアップ -->
<div id="loadingPopup" class="modal-overlay hidden">
    <div class="loading-container">
        <p>申請中...</p>
        <img src="images/loading.gif" alt="Loading">
    </div>
</div>

<!-- 完了ポップアップ -->
<div id="completePopup" class="modal-overlay hidden">
    <div class="modal">
        <p>貸出申請が完了しました。これより審査が入ります。<br>
           審査完了まで１〜４営業日ほどお待ちください。<br>
           審査状況は申請状況からご確認ください。</p>
        <button onclick="closeCompletePopup()">閉じる</button>
    </div>
</div>

<!-- JavaScriptコード -->
<script>
    let currentStep = 0;
    const steps = document.querySelectorAll(".form-step");

    function showStep(index) {
        steps.forEach((step, i) => {
            step.classList.toggle("active", i === index);
            step.classList.toggle("hidden", i !== index);
        });
    }

    function nextStep() {
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    }

    function prevStep() {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    }

    function updateQuantityField(checkbox) {
        const quantityControls = checkbox.parentElement.nextElementSibling;
        if (checkbox.checked) {
            quantityControls.style.display = 'flex';
        } else {
            quantityControls.style.display = 'none';
            quantityControls.querySelector('input').value = 1;
        }
    }

    function increaseQuantity(itemName) {
        const quantityInput = document.getElementById('quantity_' + itemName);
        quantityInput.value = parseInt(quantityInput.value) + 1;
    }

    function decreaseQuantity(itemName) {
        const quantityInput = document.getElementById('quantity_' + itemName);
        if (parseInt(quantityInput.value) > 1) {
            quantityInput.value = parseInt(quantityInput.value) - 1;
        }
    }


    document.getElementById('department_name').addEventListener('change', function() {
    document.getElementById('project_name').value = this.value;
});
document.getElementById('confirm_project_name').innerText = ''; // 空白に設定


function nextStep() {
    // 現在のステップの必須フィールドを取得
    const requiredFields = steps[currentStep].querySelectorAll("input[required], select[required]");
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add("error"); // 未入力欄にエラークラスを追加
        } else {
            field.classList.remove("error");
        }
    });

    if (isValid) {
        currentStep++;
        showStep(currentStep);
    } else {
        document.getElementById("errorPopup").classList.remove("hidden"); // エラーポップアップ表示
    }
}


function closeErrorPopup() {
    document.getElementById("errorPopup").classList.add("hidden");
}




    function showConfirmPopup() {
        const departmentName = document.getElementById('department_name').value;
        const gradeYear = document.getElementById('grade_year').value;
        const representativeName = document.getElementById('representative_name').value;
        const projectName = document.getElementById('project_name').value;
        const selectedItems = Array.from(document.querySelectorAll('input[name="rental_items[]"]:checked'))
            .map(item => item.value)
            .join(", ");
        const quantities = Array.from(document.querySelectorAll('input[name^="quantities"]'))
            .filter(input => input.closest('.quantity-controls').style.display !== 'none')
            .map(input => input.value)
            .join(", ");

        document.getElementById('confirm_department_name').innerText = departmentName;
        document.getElementById('confirm_grade_year').innerText = gradeYear;
        document.getElementById('confirm_representative_name').innerText = representativeName;
        document.getElementById('confirm_project_name').innerText = projectName;
        document.getElementById('confirm_item_name').innerText = selectedItems;
        document.getElementById('confirm_quantity').innerText = quantities;

        document.getElementById('confirmPopup').classList.add('show');
        document.getElementById('confirmPopup').classList.remove('hidden');
    }

    function closeConfirmPopup() {
        document.getElementById('confirmPopup').classList.remove('show');
        document.getElementById('confirmPopup').classList.add('hidden');
    }

    function closeCompletePopup() {
        document.getElementById('completePopup').classList.remove('show');
        document.getElementById('completePopup').classList.add('hidden');
    }

    document.getElementById('submitApplication').addEventListener('click', function(event) {
        event.preventDefault(); // デフォルトの動作を防止
        closeConfirmPopup();
        document.getElementById('loadingPopup').classList.add('show');
        document.getElementById('loadingPopup').classList.remove('hidden');

        // フォームを送信
        document.getElementById('loanForm').submit();
    });

    showStep(currentStep);
</script>
<script src="./scripts/loan_application.js"></script>

</body>
</html>
