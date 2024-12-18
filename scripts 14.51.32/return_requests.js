document.addEventListener("DOMContentLoaded", function () {
    // ハンバーガーメニューの処理
    const hamburgerIcon = document.getElementById("hamburger-icon");
    const sidebarMenu = document.getElementById("sidebar-menu");

    if (!hamburgerIcon || !sidebarMenu) {
        console.error("ハンバーガーメニューの要素が見つかりません。");
        return;
    }

    // ハンバーガーボタンをクリックしたときの動作
    hamburgerIcon.addEventListener("click", function () {
        console.log("ハンバーガーメニューがクリックされました");
        sidebarMenu.classList.toggle("open");  // サイドメニューの表示・非表示を切り替える
        hamburgerIcon.classList.toggle("active"); // ハンバーガーアイコンの変形を切り替える

        // クラスの切り替え状態を確認する
        console.log("Sidebar class:", sidebarMenu.className);
        console.log("Hamburger icon class:", hamburgerIcon.className);
    });





    // 返却申請ボタンに対する処理
    const returnButtons = document.querySelectorAll(".return-button");
    const popupOverlay = document.getElementById("popup-overlay");
    const returnPopup = document.getElementById("return-popup");

    // ボタンがクリックされたときの処理
    returnButtons.forEach(button => {
        button.addEventListener("click", function () {
            // ポップアップとオーバーレイを表示
            popupOverlay.classList.add("show");
            returnPopup.classList.add("show");

            // 選択された物品のIDを確認するためのデータを取得
            const itemId = this.getAttribute("data-item-id");
            document.getElementById("confirm-return").setAttribute("data-item-id", itemId);
        });
    });

    // 返却申請をキャンセルした場合
    document.getElementById("cancel-return").addEventListener("click", function () {
        popupOverlay.classList.remove("show");
        returnPopup.classList.remove("show");
    });

    // 返却申請が確定した場合
    document.getElementById("confirm-return").addEventListener("click", function () {
        const itemId = this.getAttribute("data-item-id");

        // 返却申請のAjaxリクエスト
        fetch("return_request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "item_id=" + encodeURIComponent(itemId)
        })
        .then(response => response.text())
        .then(data => {
            alert(data); // サーバーからのメッセージを表示

            // 該当の行を「返却申請中」に更新し、ボタンを削除
            const row = document.querySelector(`.return-button[data-item-id='${itemId}']`).closest("tr");
            const returnCell = row.querySelector("td:last-child");
            returnCell.textContent = "返却申請中"; // 「返却申請中」と表示
            row.querySelector(".return-button").remove(); // ボタンを削除

            // ポップアップを閉じる
            popupOverlay.classList.remove("show");
            returnPopup.classList.remove("show");

            // 完了メッセージを表示
            const completePopup = document.getElementById("complete-popup");
            completePopup.classList.add("show");
        })
        .catch(error => {
            console.error("返却申請エラー:", error);
            alert("返却申請に失敗しました。");
        });
    });

    // 完了メッセージを閉じるボタンの処理
    document.getElementById("close-complete").addEventListener("click", function () {
        const completePopup = document.getElementById("complete-popup");
        completePopup.classList.remove("show");
    });
});
