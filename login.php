<?php
session_start();

// 模擬帳號密碼（正式應該從資料庫讀取）
$valid_user = 'sendy';
$valid_pass = '111116001';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $valid_user && $pass === $valid_pass) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
        header("Location: home.php");
        exit;
    } else {
        $error = "帳號或密碼錯誤！";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>登入系統</title>
  <style>
    body { font-family: Arial; background: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .login-box { background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; }
    input[type=text], input[type=password] {
      width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
    }
    .btn { background: #28a745; color: white; padding: 10px; border: none; border-radius: 4px; width: 100%; cursor: pointer; }
    .btn:hover { background: #218838; }
    .error { color: red; text-align: center; }
  </style>
</head>
<body>

<div class="login-box">
  <h2>使用者登入</h2>
  <?php if ($error): ?>
    <p class="error"><?= $error ?></p>
  <?php endif; ?>
  <form method="POST">
    <label>帳號：</label>
    <input type="text" name="username" required>
    <label>密碼：</label>
    <input type="password" name="password" required>
    <button type="submit" class="btn">登入</button>
  </form>
</div>

</body>
</html>
