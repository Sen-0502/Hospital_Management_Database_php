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

if ($conn->connect_error) {
  die("連線失敗：" . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $medicine_id = $_POST['medicine_id'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $note = $_POST['note'] ?? '';
    $days = $_POST['list_days'] ?? '';

    // 驗證基本欄位
    if ($doctor_id && $patient_id && $medicine_id && $dosage && is_numeric($dosage)) {
        $list_date = date('Y-m-d');
        // 建立新藥單 List
        $stmt = $conn->prepare("INSERT INTO List (list_date, list_days) VALUES (?, ?)");
        $stmt->bind_param("si", $list_date, $days);
        if ($stmt->execute()) {
            $list_no = $conn->insert_id;

            // 新增 Prescribes
            $conn->query("INSERT INTO Prescribes (doctor_id, patient_id, list_no) VALUES ('$doctor_id', '$patient_id', '$list_no')");

            // 新增 Contains
            $stmt2 = $conn->prepare("INSERT INTO Contains (list_no, medicine_id, dosage, dosage_note) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isds", $list_no, $medicine_id, $dosage, $note);
            if ($stmt2->execute()) {
                $success_msg = "✅ 開立處方成功（編號 $list_no）";
            } else {
                $error_msg = "❌ Contains 寫入失敗：" . $stmt2->error;
            }
        } else {
            $error_msg = "❌ List 建立失敗：" . $stmt->error;
        }
    } else {
        $error_msg = "❌ 資料未填寫完整或格式錯誤";
    }
}

// 處理搜尋條件
$doctor_id = $_GET['doctor_id'] ?? '';
$patient_id = $_GET['patient_id'] ?? '';

$where = "WHERE 1";
if ($doctor_id) $where .= " AND p.doctor_id = '" . $conn->real_escape_string($doctor_id) . "'";
if ($patient_id) $where .= " AND p.patient_id = '" . $conn->real_escape_string($patient_id) . "'";

// 取得處方清單資料
$sql = "
  SELECT 
    p.doctor_id, d.doctor_name,
    p.patient_id, pa.patient_name,
    p.list_no, l.list_date, l.list_days,
    m.medicine_name, c.dosage, c.dosage_note
  FROM Prescribes p
  JOIN Doctor d ON p.doctor_id = d.doctor_id
  JOIN Patient pa ON p.patient_id = pa.patient_id
  JOIN List l ON p.list_no = l.list_no
  JOIN Contains c ON p.list_no = c.list_no
  JOIN Medicine m ON c.medicine_id = m.medicine_id
  $where
  ORDER BY p.list_no, m.medicine_name
";

$result = $conn->query($sql);
$prescriptions = [];
while ($row = $result->fetch_assoc()) {
  $key = $row['list_no'];
  if (!isset($prescriptions[$key])) {
    $prescriptions[$key] = [
      "doctor_id" => $row["doctor_id"],
      "doctor_name" => $row["doctor_name"],
      "patient_id" => $row["patient_id"],
      "patient_name" => $row["patient_name"],
      "list_date" => $row["list_date"],
      "list_days" => $row["list_days"],
      "medicines" => [],
    ];
  }
  $prescriptions[$key]["medicines"][] = [
    "name" => $row["medicine_name"],
    "dosage" => $row["dosage"],
    "note" => $row["dosage_note"]
  ];
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>處方清單</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f9f9f9; }
    h1 { color: #333; }
    form { margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
    input { padding: 6px; margin: 4px; width: 200px; box-sizing: border-box; }
    .btn { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background: #0056b3; }
    .prescription { background: #fff; padding: 15px; margin-bottom: 15px; border-radius: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
    .prescription h3 { margin: 0 0 10px; color: #444; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    .home { background: #28a745; text-decoration: none; color: white; padding: 6px 12px; border-radius: 4px; }
    .home:hover { background: #218838; }
  </style>
</head>
<body>

  <h1>📋 處方清單</h1>
  <a href="home.php" class="home">🏠 返回首頁</a>

  <!-- 搜尋 -->
  <form method="get">
    <label>醫師 ID：<input type="text" name="doctor_id" value="<?= htmlspecialchars($doctor_id) ?>"></label>
    <label>病患 ID：<input type="text" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>"></label>
    <button type="submit" class="btn">搜尋</button>
  </form>
<?php
// 取得所有藥品清單供選擇
$medResult = $conn->query("SELECT medicine_id, medicine_name FROM Medicine");
?>
<hr>
<h2>🆕 開立新處方</h2>

<?php if (isset($success_msg)) echo "<p style='color:green;'>$success_msg</p>"; ?>
<?php if (isset($error_msg)) echo "<p style='color:red;'>$error_msg</p>"; ?>

<form method="post">
  <input type="hidden" name="add_prescription" value="1">
  <label>醫師 ID：<input type="text" name="doctor_id" required></label><br>
  <label>病患 ID：<input type="text" name="patient_id" required></label><br>
  <label>處方天數：<input type="number" name="list_days" value="1" min="1" required></label><br>
  <label>選擇藥品：
    <select name="medicine_id" required>
      <?php while ($med = $medResult->fetch_assoc()): ?>
        <option value="<?= $med['medicine_id'] ?>">
          <?= $med['medicine_id'] ?> - <?= $med['medicine_name'] ?>
        </option>
      <?php endwhile; ?>
    </select>
  </label><br>
  <label>總藥量：<input type="number" step="0.01" name="dosage" required></label><br>
  <label>劑量備註：<input type="text" name="note"></label><br><br>
  <button type="submit" class="btn">➕ 開立處方</button>
</form>

  <?php if (empty($prescriptions)): ?>
    <p>查無處方資料。</p>
  <?php else: ?>
    <?php foreach ($prescriptions as $list_no => $data): ?>
      <div class="prescription">
        <h3>🧾 處方編號：<?= $list_no ?></h3>
        <p>
          👨‍⚕️ 醫師：<?= $data["doctor_name"] ?> (<?= $data["doctor_id"] ?>)<br>
          🧑‍🤝‍🧑 病患：<?= $data["patient_name"] ?> (<?= $data["patient_id"] ?>)<br>
          📅 日期：<?= $data["list_date"] ?>，天數：<?= $data["list_days"] ?>
        </p>
        <table>
          <tr>
            <th>藥品名稱</th><th>總藥量</th><th>劑量備註</th>
          </tr>
          <?php foreach ($data["medicines"] as $m): ?>
            <tr>
              <td><?= $m["name"] ?></td>
              <td><?= $m["dosage"] ?></td>
              <td><?= $m["note"] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
