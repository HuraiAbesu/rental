document.addEventListener("DOMContentLoaded", function () {
    // ポップアップを表示する関数
    function showDeletePopup(deleteType) {
        document.getElementById("delete-type").value = deleteType; // 削除タイプを設定
        document.getElementById("delete-popup").classList.add("show"); // ポップアップ表示
    }

    // ポップアップを閉じる関数
    function closeDeletePopup() {
        document.getElementById("delete-popup").classList.remove("show"); // ポップアップを非表示
    }

    // 完了メッセージポップアップを閉じる関数
    document.getElementById("close-complete").addEventListener("click", function () {
        document.getElementById("complete-popup").classList.remove("show");
    });

    // グローバルにアクセス可能にする
    window.showDeletePopup = showDeletePopup;
    window.closeDeletePopup = closeDeletePopup;
});
