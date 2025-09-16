<?php
session_start();

// 檢查是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// 登出處理
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>醫院管理系統主頁</title>
  <style>
    /* 全域設定 */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #74ebd5, #ACB6E5);
      min-height: 100vh;
      padding: 50px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      flex-direction: column;
    }

    /* 登出按鈕 */
    .logout {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: #e74c3c;
      color: white;
      padding: 10px 15px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.2s ease;
    }
    .logout:hover { background-color: #c0392b; }

    /* 標題 */
    h1 {
      color: #fff;
      text-align: center;
      margin-bottom: 40px;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }

    /* 卡片式清單 */
    .menu {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .menu a {
      background: #fff;
      padding: 20px 25px;
      border-radius: 12px;
      text-align: center;
      font-weight: bold;
      font-size: 1.1em;
      color: #2c3e50;
      text-decoration: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .menu a:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
      background: #f0f8ff;
    }

    /* icon 小調整 */
    .menu a span {
      font-size: 1.3em;
    }
  </style>
</head>
<body>
  <a href="home.php?logout=1" class="logout">🚪 登出</a>
  <h1>🏥 醫院管理系統主頁</h1>

  <div class="menu">
    <a href="patient_management.php"><span>👨‍⚕️</span> 病患管理</a>
    <a href="doctor_management.php"><span>🩺</span> 醫師管理</a>
    <a href="specialty_management.php"><span>🏢</span> 科別管理</a>
    <a href="medicine_management.php"><span>💊</span> 藥品管理</a>
    <a href="prescription_list.php"><span>📋</span> 藥單開立與查詢</a>
    <a href="treatment_management.php"><span>🧾</span> 治療紀錄管理</a>
    <a href="patient_specialty_relation.php"><span>🔗</span> 病患與科別關聯管理</a>
    <a href="report_query.php"><span>📊</span> 報表與查詢功能</a>
  </div>
</body>
</html>
