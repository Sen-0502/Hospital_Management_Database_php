<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$password = "111116001";
$database = "hospital_db";
$conn = new mysqli($host, $user, $password, $database);
$conn->set_charset("utf8");
if ($conn->connect_error) die("連線失敗：" . $conn->connect_error);

$doctor_id = $_GET['doctor_id'] ?? '';
$patient_id = $_GET['patient_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = "WHERE 1";
if ($doctor_id) $where .= " AND d.doctor_id = '" . $conn->real_escape_string($doctor_id) . "'";
if ($patient_id) $where .= " AND p.patient_id = '" . $conn->real_escape_string($patient_id) . "'";
if ($start_date) $where .= " AND l.list_date >= '" . $conn->real_escape_string($start_date) . "'";
if ($end_date) $where .= " AND l.list_date <= '" . $conn->real_escape_string($end_date) . "'";

// SQL取出藥品價格
$sql = "
SELECT 
  d.doctor_name, p.patient_name, l.list_no, l.list_date,
  m.medicine_name, m.medicine_price, c.dosage, c.dosage_note
FROM Prescribes pr
JOIN Doctor d ON pr.doctor_id = d.doctor_id
JOIN Patient p ON pr.patient_id = p.patient_id
JOIN List l ON pr.list_no = l.list_no
JOIN Contains c ON l.list_no = c.list_no
JOIN Medicine m ON c.medicine_id = m.medicine_id
$where
ORDER BY l.list_date DESC, l.list_no
";

$result = $conn->query($sql);

// 先把資料用陣列存起來，並依 list_no 分組計算總金額
$prescriptions = [];
while ($row = $result->fetch_assoc()) {
    $list_no = $row['list_no'];
    $amount = $row['medicine_price'] * $row['dosage']; // 單價 * 劑量
    
    if (!isset($prescriptions[$list_no])) {
        $prescriptions[$list_no] = [
            'doctor_name' => $row['doctor_name'],
            'patient_name' => $row['patient_name'],
            'list_date' => $row['list_date'],
            'items' => [],
            'total_amount' => 0,
        ];
    }
    $prescriptions[$list_no]['items'][] = [
        'medicine_name' => $row['medicine_name'],
        'dosage' => $row['dosage'],
        'dosage_note' => $row['dosage_note'],
        'medicine_price' => $row['medicine_price'],
    ];
    $prescriptions[$list_no]['total_amount'] += $amount;
}

// 下拉選單
$doctors = $conn->query("SELECT doctor_id, doctor_name FROM Doctor");
$patients = $conn->query("SELECT patient_id, patient_name FROM Patient");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <title>處方查詢報表</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
    h1 { color: #2c3e50; margin: 0; }
    .card {
      background: #fff; padding: 20px; margin-bottom: 20px;
      border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    select, input[type="date"], .btn {
      padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;
    }
    .btn {
      background: #2c7be5; color: white; border: none; cursor: pointer; transition: 0.2s;
    }
    .btn:hover { background: #1a5dc9; }
    .home {
      background: #28a745; text-decoration: none; color: white; padding: 8px 14px;
      border-radius: 6px; font-size: 14px; transition: 0.2s;
    }
    .home:hover { background: #218838; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 20px;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #2c7be5; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .total { font-weight: bold; color: #d9534f; }
  </style>
</head>
<body>

<div class="topbar">
  <h1>📊 處方查詢報表</h1>
  <a href="home.php" class="home">🏠 返回首頁</a>
</div>

<div class="card">
  <form method="get">
    <label>醫師：
      <select name="doctor_id">
        <option value="">全部</option>
        <?php while ($row = $doctors->fetch_assoc()): ?>
          <option value="<?= $row['doctor_id'] ?>" <?= $doctor_id == $row['doctor_id'] ? 'selected' : '' ?>>
            <?= $row['doctor_id'] ?> - <?= $row['doctor_name'] ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>

    <label>病患：
      <select name="patient_id">
        <option value="">全部</option>
        <?php while ($row = $patients->fetch_assoc()): ?>
          <option value="<?= $row['patient_id'] ?>" <?= $patient_id == $row['patient_id'] ? 'selected' : '' ?>>
            <?= $row['patient_id'] ?> - <?= $row['patient_name'] ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>

    <label>日期區間：
      <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
      ~
      <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    </label>

    <button type="submit" class="btn">查詢</button>
  </form>
</div>

<div class="card">
  <table>
    <tr>
      <th>日期</th>
      <th>處方編號</th>
      <th>醫師姓名</th>
      <th>病患姓名</th>
      <th>藥品名稱</th>
      <th>單價</th>
      <th>總藥量</th>
      <th>劑量備註</th>
    </tr>

    <?php if (!empty($prescriptions)): ?>
      <?php foreach ($prescriptions as $list_no => $prescription): ?>
        <?php $rowspan = count($prescription['items']); $first = true; ?>
        <?php foreach ($prescription['items'] as $item): ?>
          <tr>
            <?php if ($first): ?>
              <td rowspan="<?= $rowspan ?>"><?= $prescription['list_date'] ?></td>
              <td rowspan="<?= $rowspan ?>">
                <?= $list_no ?><br>
                <span class="total">總金額：<?= number_format($prescription['total_amount'], 2) ?> 元</span>
              </td>
              <td rowspan="<?= $rowspan ?>"><?= $prescription['doctor_name'] ?></td>
              <td rowspan="<?= $rowspan ?>"><?= $prescription['patient_name'] ?></td>
              <?php $first = false; ?>
            <?php endif; ?>
            <td><?= $item['medicine_name'] ?></td>
            <td><?= number_format($item['medicine_price'], 2) ?></td>
            <td><?= $item['dosage'] ?></td>
            <td><?= htmlspecialchars($item['dosage_note']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="8">查無資料</td></tr>
    <?php endif; ?>
  </table>
</div>

</body>
</html>

<?php $conn->close(); ?>
