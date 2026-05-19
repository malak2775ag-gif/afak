<?php
$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$gameUrl = "Find the words.html"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFAK Learning Challenge</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --afak-blue: #1e3c72;
            --afak-gold: #f39c12;
            --bg-color: #f4f7f6;
        }

        body {
            font-family: 'Poppins', 'Tajawal', sans-serif;
            background: transparent;
            margin: 0;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .game-wrapper {
            background: white;
            width: 100%;
            max-width: 1200px;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeIn 0.8s ease-in-out;
        }

        .header {
            background: var(--afak-blue);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 5px solid var(--afak-gold);
        }

        .header h1 { margin: 0; font-size: 20px; font-weight: 700; text-transform: uppercase; }
        .header p { margin: 5px 0 0; opacity: 0.8; font-size: 13px; }

        .iframe-container {
            padding: 15px;
            display: flex;
            justify-content: center;
            background: #fff;
        }

        iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 15px;
        }

        .footer {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="game-wrapper">
        <div class="header">
            <h1>🧩 AFAK Brain Explorer</h1>
            <p>Interactive Activity #<?= $materialId ?></p>
        </div>

        <div class="iframe-container">
            <!-- Use the programming variable to set the game file name -->
            <iframe src="<?= $gameUrl ?>" title="AFAK Educational Game"></iframe>
        </div>

        <div class="footer">
            &copy; 2026 AFAK Platform | UTAS-Ibri Smart Education Project
        </div>
    </div>

</body>
</html>