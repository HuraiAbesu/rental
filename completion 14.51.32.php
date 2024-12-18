<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請完了 - 物品ナビ</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: space-between;
        }
        header {
            background: linear-gradient(145deg, #00a1cc, #007ba3);
            color: white;
            text-align: center;
            padding: 15px 0;
            font-size: 24px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .container {
            text-align: center;
            padding: 40px 20px;
            flex: 1;
        }
        .completion-icon {
            font-size: 50px;
            color: #00a1cc;
            margin-bottom: 20px;
        }
        .completion-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #333;
        }
        .status-button {
            background: linear-gradient(145deg, #00a1cc, #007ba3);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
            display: inline-block;
            margin-top: 20px;
        }
        .status-button:hover {
            background: linear-gradient(145deg, #007ba3, #00a1cc);
            box-shadow: 0px 6px 8px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        footer {
            background: linear-gradient(145deg, #e0e0e0, #f0f0f0);
            color: #666;
            text-align: center;
            padding: 15px 0;
            font-size: 14px;
            box-shadow: 0px -4px 6px rgba(0, 0, 0, 0.1);
        }
        /* モバイルフレンドリー設定 */
        @media (max-width: 600px) {
            header {
                font-size: 20px;
            }
            .completion-message {
                font-size: 16px;
            }
            .status-button {
                font-size: 14px;
                padding: 10px 20px;
            }
            .container {
                padding: 30px 15px;
            }
        }
    </style>
</head>
<body>

<header>
    物品ナビ
</header>

<div class="container">
    <div class="completion-icon">✔️</div>
    <div class="completion-message">
        <p>申請完了</p>
        <p>貸出申請が完了しました。これより審査が入ります。</p>
        <p>審査完了まで１〜４営業日ほどお待ちください。</p>
        <p>審査状況は申請状況からご確認ください。</p>
    </div>
    <a href="./return_requests" class="status-button">申請状況</a>
</div>

<footer>
    物品ナビ Powered by Synfortech
</footer>

</body>
</html>
