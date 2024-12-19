document.addEventListener("DOMContentLoaded", function () {
    // エラーポップアップを閉じる関数を定義
    window.closeErrorPopup = function () {
        document.getElementById("errorPopup").classList.remove("show");
    };

    // エラーポップアップを表示
    function showErrorPopup() {
        document.getElementById("errorPopup").classList.add("show");
    }

    // 確認ポップアップを表示
    function showConfirmPopup() {
        const itemName = document.getElementById("item").value;  // selectタグのidは "item"
        const departmentName = document.getElementById("department_name").value;
        const gradeYear = document.querySelector('input[name="grade_year"]:checked');
        const representativeName = document.getElementById("representative_name").value;
        const projectName = document.getElementById("project_name").value;
        const quantity = document.getElementById("quantity").value;

        // ラジオボタンが選択されていない場合はnull
        const gradeYearValue = gradeYear ? gradeYear.value : null;

        // 必須項目のチェック
        if (itemName && departmentName && gradeYearValue && representativeName && projectName && quantity) {
            document.getElementById("confirm_item_name").innerText = itemName;
            document.getElementById("confirm_department_name").innerText = departmentName;
            document.getElementById("confirm_grade_year").innerText = gradeYearValue;
            document.getElementById("confirm_representative_name").innerText = representativeName;
            document.getElementById("confirm_project_name").innerText = projectName;
            document.getElementById("confirm_quantity").innerText = quantity;
            document.getElementById("confirmPopup").classList.add("show");
        } else {
            showErrorPopup();  // 必須項目が空の場合、エラーポップアップを表示
        }
    }

   // 確認ポップアップを閉じる
   function closeConfirmPopup() {
        document.getElementById("confirmPopup").classList.remove("show");
    }

    // 再編集ボタンのクリックイベントを追加
    document.getElementById("editButton").addEventListener("click", closeConfirmPopup);

    // 完了ポップアップを表示
    function showCompletePopup() {
        document.getElementById("completePopup").classList.add("show");
    }

    // 完了ポップアップを閉じる
    function closeCompletePopup() {
        document.getElementById("completePopup").classList.remove("show");

        // フォーム送信
        document.getElementById("loanForm").submit();
    }

    // 前のステップに戻る処理
    document.querySelectorAll('.prev-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const currentStep = this.closest('.form-step');
            const prevStep = currentStep.previousElementSibling;
        
            if (prevStep) {
                currentStep.classList.remove('active');
                prevStep.classList.add('active');
            }
        });
    });

    // フォームのバリデーションチェックと次のステップに進む処理
    document.querySelectorAll('.next-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const inputs = this.closest('.form-step').querySelectorAll('input, select');
            let allFilled = true;
    
            inputs.forEach(input => {
                if (input.type === 'radio') {
                    // ラジオボタンが1つでも選択されているか確認
                    const radioChecked = document.querySelector('input[name="' + input.name + '"]:checked');
                    if (!radioChecked) {
                        allFilled = false;
                    }
                } else if (input.tagName === 'SELECT') {
                    // selectタグの値が空でないことを確認
                    if (input.value === "" || input.value === null) {
                        allFilled = false;
                    }
                } else if (!input.value) {
                    allFilled = false;
                }
            });
    
            if (!allFilled) {
                showErrorPopup();  // 入力が不足している場合はエラーポップアップを表示
            } else {
                this.closest('.form-step').classList.remove('active');
                this.closest('.form-step').nextElementSibling.classList.add('active');
            }
        });
    });

    // 申請するボタンの確認ポップアップ
    document.getElementById("showConfirmPopup").addEventListener("click", function (event) {
        event.preventDefault();  // デフォルトの送信を防ぐ

        const inputs = document.querySelectorAll("input, select");
        let allFilled = true;

        inputs.forEach(input => {
            if (input.type === 'radio') {
                // ラジオボタンが選択されているか確認
                const radioChecked = document.querySelector('input[name="' + input.name + '"]:checked');
                if (!radioChecked) {
                    allFilled = false;
                }
            } else if (input.tagName === 'SELECT' && (input.value === "" || input.value === null)) {
                allFilled = false;
            } else if (!input.value) {
                allFilled = false;
            }
        });

        if (allFilled) {
            showConfirmPopup();  // 入力が正しければ確認ポップアップを表示
        } else {
            showErrorPopup();  // 入力不足があればエラーポップアップを表示
        }
    });

    // 申請を確定するボタンが押されたときに実際にフォームを送信
    document.getElementById("submitApplication").addEventListener("click", function (event) {
        event.preventDefault();  // ページリロードを防ぐ
        closeConfirmPopup();  // 確認ポップアップを閉じる
        showCompletePopup();  // 完了ポップアップを表示
    });

    // 完了ポップアップの「閉じる」ボタンが押されたときにフォーム送信
    document.querySelector("#completePopup button").addEventListener("click", closeCompletePopup);
});


document.addEventListener("DOMContentLoaded", function () {
    // ハンバーガーメニューの要素を取得
    const hamburgerIcon = document.querySelector(".openbtn");
    const sidebarMenu = document.querySelector(".hamburger-menu");

    if (!hamburgerIcon || !sidebarMenu) {
        console.error("ハンバーガーメニューの要素が見つかりません。");
        return;
    }

    // ハンバーガーボタンをクリックしたときの動作
    hamburgerIcon.addEventListener("click", function () {
        hamburgerIcon.classList.toggle("active"); // Xアイコンの切り替え
        sidebarMenu.classList.toggle("menu-open"); // メニューの表示/非表示切り替え
    });
});
