<?php
require '../db_connection.php'; // データベース接続ファイルをインクルード

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_version'])) {
    // システムバージョンの確認
    $query = "SELECT system_version FROM settings WHERE id = 1";
    $result = $shop_conn->query($query);

    if ($result) {
        $db_version = $result->fetch_assoc()['system_version'];
        $php_version = '10.5.30'; // PHPで管理しているシステムバージョン

        if ($db_version === $php_version) {
            echo json_encode(['status' => 'success', 'db_version' => $db_version, 'php_version' => $php_version]);
        } else {
            echo json_encode(['status' => 'version_mismatch', 'db_version' => $db_version, 'php_version' => $php_version]);
        }
    } else {
        echo json_encode(['status' => 'query_error']);
    }
    $shop_conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EX Secure</title>
    <style>
body {
    font-family: Arial, sans-serif;
    text-align: center;
    padding-top: 30px;
    margin: 0;
    font-size: 20px; /* フォントサイズを大きくしました */
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #f1f1f1;
}

.header .logo {
    color: #00a1cc;
    font-size: 28px;
}

.header .title img {
    height: 50px;
}

#loading, #browser-support-message, #version-check-message, #result, #error-message, #redirecting {
    display: none;
}

#browser-image, #server-icon, #user-icon {
    max-width: 100px; /* 画像サイズを大きくしました */
    margin-top: 20px;
}

.spinner {
    border: 6px solid #f3f3f3;
    border-top: 6px solid #3498db;
    border-radius: 50%;
    width: 60px; /* スピナーサイズを大きくしました */
    height: 60px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-message {
    color: red;
    margin-top: 25px;
    font-size: 22px; /* エラーメッセージのフォントサイズを大きくしました */
}

.status-icon {
    font-size: 32px; /* ステータスアイコンのサイズを大きくしました */
    margin-top: 20px;
}

.status-icon.ok {
    color: green;
}

.status-icon.ng {
    color: red;
}

.version-info {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.version-info img {
    margin-right: 12px;
}

footer {
    margin-top: auto;
    padding: 20px;
    background-color: #f1f1f1;
    text-align: center;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
}

footer p {
    margin: 5px 0;
}

.footer-link {
    color: #00a1cc;
    text-decoration: none;
}

.footer-link:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    body {
        font-size: 18px; /* モバイル時のフォントサイズも少し大きく */
    }
    .header .logo {
        font-size: 22px;
    }
    .header .title img {
        height: 40px;
    }
    #browser-image, #server-icon, #user-icon {
        max-width: 85px; /* モバイル用に画像サイズも大きく */
    }
    .status-icon {
        font-size: 28px; /* モバイルでもステータスアイコンを大きめに */
    }
}

    </style>
</head>
<body>
    <div class="header">
        <div class="logo"><strong>Synfortech</strong></div>
        <div class="title"><img src="icon/exsecure.png" alt="EX Secure"></div>
    </div>

    <div id="loading">
        <div class="spinner"></div>
        <p>読み込み中...</p>
    </div>

    <div id="browser-support-message">
        <p>ブラウザのチェックをしています...</p>
        <img id="browser-image" src="" alt="ブラウザの画像">
        <p id="browser-name"></p>
    </div>

    <div id="version-check-message">
        <p>システムバージョンをチェックしています...</p>
        <div class="version-info">
            <img id="server-icon" src="icon/server.png" alt="サーバーシステムアイコン">
            <p>データベースのバージョン: <span id="db-version"></span></p>
        </div>
        <div class="version-info">
            <img id="user-icon" src="icon/user.png" alt="ユーザーシステムアイコン">
            <p>ユーザーのバージョン: <span id="user-version"></span></p>
        </div>
    </div>

    <div id="result">
        <p>チェック結果:</p>
        <div>ブラウザ: <span id="browser-status" class="status-icon"></span></div>
        <div>システムバージョン: <span id="version-status" class="status-icon"></span></div>
    </div>

    <div id="error-message" class="error-message">
        <p>ご利用いただけません。</p>
        <p>対応ブラウザ：Chrome　Safari</p>
    </div>

    <div id="redirecting">
        <div class="spinner"></div>
        <p>リダイレクト中...</p>
    </div>

    <footer>
        <p><a href="exsecurehelp" class="footer-link">このページは何ですか？</a></p>
        <p><strong>EX Secure</strong> | Powered by Synfortech</p>
    </footer>

    <script>
        function detectBrowser() {
            const userAgent = navigator.userAgent.toLowerCase();
            let browserImage = "secureimg/web.png"; // デフォルトの画像
            let browserName = "サポートされていないブラウザ";
            let supported = false;
// ブラウザを検出し、アイコンと名前を設定
if (userAgent.includes("crios")) {
    // Chrome on iOS
    browserImage = "secureimg/chrome.png";
    browserName = "Chrome (iOS)";
    supported = true;
} else if (userAgent.includes("fxios")) {
    // Firefox on iOS
    browserImage = "secureimg/firefox.png";
    browserName = "Firefox (iOS)";
    supported = false;
} else if (userAgent.includes("edgios")) {
    // Edge on iOS
    browserImage = "secureimg/edge.png";
    browserName = "Edge (iOS)";
    supported = false;
} else if (userAgent.includes("safari") && !userAgent.includes("chrome") && !userAgent.includes("crios") && userAgent.includes("iphone")) {
    // Safari on iPhone
    browserImage = "secureimg/safari.png";
    browserName = "Safari (iPhone)";
    supported = true;
} else if (userAgent.includes("safari") && !userAgent.includes("chrome") && !userAgent.includes("crios") && !userAgent.includes("iphone")) {
    // Safari on Mac
    browserImage = "secureimg/safari.png";
    browserName = "Safari (Mac)";
    supported = true;
} else if (userAgent.includes("chrome") && userAgent.includes("dev") && !userAgent.includes("edg") && !userAgent.includes("opr")) {
    // Chrome Developer Edition
    browserImage = "secureimg/chrome-dev.png";
    browserName = "Chrome Developer Edition";
    supported = true;
} else if (userAgent.includes("chrome") && !userAgent.includes("edg") && !userAgent.includes("opr")) {
    // Chrome
    browserImage = "secureimg/chrome.png";
    browserName = "Chrome";
    supported = true;
} else if (userAgent.includes("firefox")) {
    // Firefox
    browserImage = "secureimg/firefox.png";
    browserName = "Firefox";
    supported = false;
} else if (userAgent.includes("edg")) {
    // Edge
    browserImage = "secureimg/edge.png";
    browserName = "Edge";
    supported = true;
} else if (userAgent.includes("opr") || userAgent.includes("opera")) {
    // Opera
    browserImage = "secureimg/opera.png";
    browserName = "Opera";
    supported = false;
} else if (userAgent.includes("samsung")) {
    // Samsung Internet
    browserImage = "secureimg/samsung-internet.png";
    browserName = "Samsung Internet";
    supported = false;
} else if (userAgent.includes("duckduckgo")) {
    // DuckDuckGo
    browserImage = "secureimg/duckduck.png";
    browserName = "DuckDuckGo";
    supported = false;
} else if (userAgent.includes("android")) {
    // Android Browser
    browserImage = "secureimg/android-browser.png";
    browserName = "Android Browser";
    supported = false;
} else {
    // その他のブラウザ
    browserImage = "secureimg/web.png";
    browserName = "サポートされていないブラウザ";
    supported = false;
}



            document.getElementById("browser-image").src = browserImage;
            document.getElementById("browser-name").textContent = browserName;
            return supported;
        }

        function checkSystemVersion() {
            // AJAXリクエストでシステムバージョンを確認
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "exsecure.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        document.getElementById("db-version").textContent = response.db_version;
                        document.getElementById("user-version").textContent = response.php_version;
                        setTimeout(showResult, 4000, detectBrowser(), true);
                    } else {
                        document.getElementById("db-version").textContent = response.db_version;
                        document.getElementById("user-version").textContent = response.php_version;
                        setTimeout(showResult, 3000, detectBrowser(), false);
                    }
                } else if (xhr.readyState === 4) {
                    setTimeout(showResult, 4000, false, false);
                }
            };
            xhr.send("check_version=true");
        }

        function showResult(browserOk, versionOk) {
            document.getElementById("version-check-message").style.display = "none";
            document.getElementById("result").style.display = "block";

            const browserStatus = document.getElementById("browser-status");
            const versionStatus = document.getElementById("version-status");

            browserStatus.textContent = browserOk ? "○" : "×";
            browserStatus.className = "status-icon " + (browserOk ? "ok" : "ng");

            versionStatus.textContent = versionOk ? "○" : "×";
            versionStatus.className = "status-icon " + (versionOk ? "ok" : "ng");

            if (!browserOk || !versionOk) {
                document.getElementById("error-message").style.display = "block";
            } else {
                document.getElementById("redirecting").style.display = "block";
                setTimeout(() => window.location.href = "admin_dashboard", 4000);
            }
        }

        window.onload = function () {
            document.getElementById("loading").style.display = "block";

            setTimeout(function () {
                document.getElementById("loading").style.display = "none";
                document.getElementById("browser-support-message").style.display = "block";
                const browserSupported = detectBrowser();

                setTimeout(function () {
                    document.getElementById("browser-support-message").style.display = "none";
                    document.getElementById("version-check-message").style.display = "block";
                    checkSystemVersion(browserSupported);
                }, 2000);
            }, 1000);
        };
    </script>
</body>
</html>
