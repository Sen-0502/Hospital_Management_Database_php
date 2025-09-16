<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>

<?php
$host = "localhost";
$user = "root";
$password = "111116001";
$database = "hospital_db";
$conn = new mysqli($host, $user, $password, $database);
$conn->set_charset("utf8");
if ($conn->connect_error) die("連線失敗：" . $conn->connect_error);

// 刪除
if (isset($_GET['delete'])) {
  $sid = $conn->real_escape_string($_GET['sid']);
  $pid = $conn->real_escape_string($_GET['pid']);
  $conn->query("DELETE FROM P_belong_S WHERE specialty_id='$sid' AND patient_id='$pid'");
}

// 新增
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
  $sid = $conn->real_escape_string($_POST['specialty_id']);
  $pid = $conn->real_escape_string($_POST['patient_id']);
  $check = $conn->query("SELECT * FROM P_belong_S WHERE specialty_id='$sid' AND patient_id='$pid'");
  if ($check->num_rows == 0) {
    $conn->query("INSERT INTO P_belong_S VALUES ('$sid', '$pid')");
  } else {
    echo "<script>alert('此病患與科別的關聯已存在。');</script>";
  }
}

// 搜尋條件
$filter_sid = $_GET['specialty_id'] ?? '';
$filter_pid = $_GET['patient_id'] ?? '';
$where = "WHERE 1";
if ($filter_sid) $where .= " AND ps.specialty_id = '" . $conn->real_escape_string($filter_sid) . "'";
if ($filter_pid) $where .= " AND ps.patient_id = '" . $conn->real_escape_string($filter_pid) . "'";

// 查詢關聯清單
$sql = "
  SELECT ps.*, s.specialty_name, p.patient_name
  FROM P_belong_S ps
  JOIN Specialty s ON ps.specialty_id = s.specialty_id
  JOIN Patient p ON ps.patient_id = p.patient_id
  $where
  ORDER BY ps.patient_id,ps.specialty_id
";
$result = $conn->query($sql);

// 下拉清單
$specialties = $conn->query("SELECT specialty_id, specialty_name FROM Specialty");
$patients = $conn->query("SELECT patient_id, patient_name FROM Patient");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>病患與科別關聯管理</title>
  <style>
    body { font-family: Arial; background: #f1f1f1; padding: 20px; }
    h1 { color: #333; }
    form, table { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
    table { width: 100%; margin-top: 15px; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    input, select { padding: 6px; margin: 4p; box-sizing: border-box; }
    .btn { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background: #0056b3; }
    .danger { background: #dc3545; }
    .danger:hover { background: #c82333; }
    .home { background: #28a745; text-decoration: none; color: white; padding: 6px 12px; border-radius: 4px; }
    .home:hover { background: #218838; }
  </style>
</head>
<body>

<h1>🏥 病患與科別關聯管理</h1>
<a href="home.php" class="home">🏠 返回首頁</a>

<!-- 搜尋 -->
<form method="get">
  <label>病患 ID：<input type="text" name="patient_id" value="<?= htmlspecialchars($filter_pid) ?>"></label>
  <label>科別 ID：<input type="text" name="specialty_id" value="<?= htmlspecialchars($filter_sid) ?>"></label>
  <button type="submit" class="btn">搜尋</button>
</form>

<!-- 新增 -->
<form method="post">
  <input type="hidden" name="add" value="1">
  <label>病患：
    <select name="patient_id" required>
      <option value="">請選擇</option>
      <?php while($row = $patients->fetch_assoc()): ?>
        <option value="<?= $row['patient_id'] ?>"><?= $row['patient_id'] ?> - <?= $row['patient_name'] ?></option>
      <?php endwhile; ?>
    </select>
  </label>
  <label>科別：
    <select name="specialty_id" required>
      <option value="">請選擇</option>
      <?php
      $specialties->data_seek(0);
      while($row = $specialties->fetch_assoc()): ?>
        <option value="<?= $row['specialty_id'] ?>"><?= $row['specialty_id'] ?> - <?= $row['specialty_name'] ?></option>
      <?php endwhile; ?>
    </select>
  </label>
  <button type="submit" class="btn">新增關聯</button>
</form>

<!-- 顯示 -->
<table>
  <tr>
    <th>病患 ID</th><th>病患姓名</th><th>科別 ID</th><th>科別名稱</th><th>操作</th>
  </tr>
  <?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['patient_id'] ?></td>
        <td><?= $row['patient_name'] ?></td>
        <td><?= $row['specialty_id'] ?></td>
        <td><?= $row['specialty_name'] ?></td>
        <td>
          <a class="btn danger" href="?delete=1&sid=<?= $row['specialty_id'] ?>&pid=<?= $row['patient_id'] ?>" onclick="return confirm('確定刪除關聯？')">刪除</a>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="5">查無資料</td></tr>
  <?php endif; ?>
</table>

</body>
</html>

<?php $conn->close(); ?>
