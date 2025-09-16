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
  $did = $conn->real_escape_string($_GET['did']);
  $pid = $conn->real_escape_string($_GET['pid']);
  $date = $conn->real_escape_string($_GET['date']);
  $conn->query("DELETE FROM Treatment WHERE doctor_id='$did' AND patient_id='$pid' AND treatment_date='$date'");
}

// 新增
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $did = $conn->real_escape_string($_POST['doctor_id']);
  $pid = $conn->real_escape_string($_POST['patient_id']);
  $date = $conn->real_escape_string($_POST['treatment_date']);
  $cert = $conn->real_escape_string($_POST['certificate']);
  $check = $conn->query("SELECT * FROM Treatment WHERE doctor_id='$did' AND patient_id='$pid' AND treatment_date='$date'");
  if ($check->num_rows == 0) {
    $conn->query("INSERT INTO Treatment VALUES ('$did', '$pid', '$date', '$cert')");
  } else {
    echo "<script>alert('此診療紀錄已存在。');</script>";
  }
}

// 更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
  $old_did = $conn->real_escape_string($_POST['old_doctor_id']);
  $old_pid = $conn->real_escape_string($_POST['old_patient_id']);
  $old_date = $conn->real_escape_string($_POST['old_treatment_date']);
  $cert = $conn->real_escape_string($_POST['certificate']);

  $conn->query("UPDATE Treatment SET certificate='$cert' 
    WHERE doctor_id='$old_did' AND patient_id='$old_pid' AND treatment_date='$old_date'");
}

// 搜尋條件
$filter_doctor = $_GET['doctor_id'] ?? '';
$filter_patient = $_GET['patient_id'] ?? '';
$where = "WHERE 1";
if ($filter_doctor) $where .= " AND t.doctor_id = '" . $conn->real_escape_string($filter_doctor) . "'";
if ($filter_patient) $where .= " AND t.patient_id = '" . $conn->real_escape_string($filter_patient) . "'";

// 查詢紀錄
$sql = "
  SELECT t.*, d.doctor_name, p.patient_name
  FROM Treatment t
  JOIN Doctor d ON t.doctor_id = d.doctor_id
  JOIN Patient p ON t.patient_id = p.patient_id
  $where
  ORDER BY t.treatment_date DESC
";
$result = $conn->query($sql);

// 取得下拉資料
$doctors = $conn->query("SELECT doctor_id, doctor_name FROM Doctor");
$patients = $conn->query("SELECT patient_id, patient_name FROM Patient");

// 是否編輯模式
$edit = isset($_GET['edit']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>診療紀錄管理</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    h1 { color: #333; }
    form, table { background: #fff; padding: 15px; margin-top: 20px; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
    input, select { padding: 5px; box-sizing: border-box; }
    .btn { padding: 5px 10px; border-radius: 4px; cursor: pointer; border: none; }
    .edit { background-color: #28a745; color: white; }
    .delete { background-color: crimson; color: white; }
    .submit { background-color: green; color: white; }
    .home { background: #007bff; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
    .home:hover { background: #0056b3; }
  </style>
</head>
<body>

<h1>🩺 診療紀錄管理</h1>
<a class="home" href="home.php">🏠 返回首頁</a>

<!-- 篩選 -->
<form method="get">
  <label>醫師ID：<input type="text" name="doctor_id" value="<?= htmlspecialchars($filter_doctor) ?>"></label>
  <label>病患ID：<input type="text" name="patient_id" value="<?= htmlspecialchars($filter_patient) ?>"></label>
  <button class="btn" type="submit">搜尋</button>
</form>

<!-- 新增 -->
<?php if (!$edit): ?>
<form method="post">
  <input type="hidden" name="add" value="1">
  <label>醫師：
    <select name="doctor_id" required>
      <option value="">請選擇</option>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= $d['doctor_id'] ?>"><?= $d['doctor_id'] ?> - <?= $d['doctor_name'] ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>病患：
    <select name="patient_id" required>
      <option value="">請選擇</option>
      <?php foreach ($patients as $p): ?>
        <option value="<?= $p['patient_id'] ?>"><?= $p['patient_id'] ?> - <?= $p['patient_name'] ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>診療日期：<input type="date" name="treatment_date" required></label>
  <label>診斷證明：<input type="text" name="certificate" required></label>
  <button type="submit" class="btn submit">新增紀錄</button>
</form>
<?php endif; ?>

<!-- 編輯模式 -->
<?php if ($edit): 
  $ed = $_GET['did'];
  $ep = $_GET['pid'];
  $edate = $_GET['date'];
  $editrow = $conn->query("SELECT * FROM Treatment WHERE doctor_id='$ed' AND patient_id='$ep' AND treatment_date='$edate'")->fetch_assoc();
?>
<form method="post">
  <input type="hidden" name="update" value="1">
  <input type="hidden" name="old_doctor_id" value="<?= $editrow['doctor_id'] ?>">
  <input type="hidden" name="old_patient_id" value="<?= $editrow['patient_id'] ?>">
  <input type="hidden" name="old_treatment_date" value="<?= $editrow['treatment_date'] ?>">
  <label>診斷證明：<input type="text" name="certificate" value="<?= htmlspecialchars($editrow['certificate']) ?>" required></label>
  <button type="submit" class="btn submit">儲存</button>
  <a href="treatment_management.php" class="btn">取消</a>
</form>
<?php endif; ?>

<!-- 顯示紀錄 -->
<table>
  <tr>
    <th>醫師</th><th>病患</th><th>日期</th><th>診斷證明</th><th>操作</th>
  </tr>
  <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['doctor_name'] ?> (<?= $row['doctor_id'] ?>)</td>
      <td><?= $row['patient_name'] ?> (<?= $row['patient_id'] ?>)</td>
      <td><?= $row['treatment_date'] ?></td>
      <td><?= $row['certificate'] ?></td>
      <td>
        <a class="btn edit" href="?edit=1&did=<?= $row['doctor_id'] ?>&pid=<?= $row['patient_id'] ?>&date=<?= $row['treatment_date'] ?>">編輯</a>
        <a class="btn delete" href="?delete=1&did=<?= $row['doctor_id'] ?>&pid=<?= $row['patient_id'] ?>&date=<?= $row['treatment_date'] ?>" onclick="return confirm('確認刪除？')">刪除</a>
      </td>
    </tr>
  <?php endwhile; ?>
</table>

</body>
</html>

<?php $conn->close(); ?>
