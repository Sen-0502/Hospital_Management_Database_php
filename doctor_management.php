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
  die("資料庫連線失敗：" . $conn->connect_error);
}

// 所有科別下拉選單
$specialties = [];
$res = $conn->query("SELECT * FROM Specialty");
while ($row = $res->fetch_assoc()) {
  $specialties[] = $row;
}

$message = "";

// 刪除醫師
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $stmt = $conn->prepare("DELETE FROM Doctor WHERE doctor_id = ?");
  $stmt->bind_param("s", $id);
  if ($stmt->execute()) {
    $message = "<p style='color: green;'>✅ 醫師已刪除！</p>";
  } else {
    $message = "<p style='color: red;'>❌ 刪除失敗！</p>";
  }
  $stmt->close();
}

// 新增或更新醫師
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_POST["doctor_id"];
  $name = $_POST["doctor_name"];
  $sex = $_POST["doctor_sex"];
  $phone = $_POST["doctor_phone"];
  $address = $_POST["doctor_address"];
  $birthday = $_POST["doctor_birthday"];
  $start_date = $_POST["doctor_start_date"];
  $specialty_no = $_POST["specialty_no"];

  // 編輯模式
  if (isset($_POST["edit_mode"])) {
    $stmt = $conn->prepare("UPDATE Doctor SET doctor_name=?, doctor_sex=?, doctor_phone=?, doctor_address=?, doctor_birthday=?, doctor_start_date=?, specialty_no=? WHERE doctor_id=?");
    $stmt->bind_param("ssssssss", $name, $sex, $phone, $address, $birthday, $start_date, $specialty_no, $id);
    $stmt->execute();
    $stmt->close();
    $message = "<p style='color: green;'>✅ 醫師資料已更新！</p>";
  } else {
    // 檢查重複
    $check = $conn->prepare("SELECT * FROM Doctor WHERE doctor_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $message = "<p style='color: red;'>❌ 醫師 ID 已存在！</p>";
    } else {
      $stmt = $conn->prepare("INSERT INTO Doctor VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssssss", $id, $name, $sex, $phone, $address, $birthday, $start_date, $specialty_no);
      $stmt->execute();
      $stmt->close();
      $message = "<p style='color: green;'>✅ 醫師新增成功！</p>";
    }
    $check->close();
  }
}

// 搜尋功能
$search_name = $_GET['search_name'] ?? '';
$search_specialty = $_GET['search_specialty'] ?? '';
$where = [];

if ($search_name) $where[] = "d.doctor_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
if ($search_specialty) $where[] = "s.specialty_id = '" . $conn->real_escape_string($search_specialty) . "'";

$where_clause = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$doctor_list = $conn->query("SELECT d.*, s.specialty_name FROM Doctor d LEFT JOIN Specialty s ON d.specialty_no = s.specialty_id $where_clause ORDER BY d.doctor_id");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>醫師管理</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f9f9f9; }
    form, table { background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { border: 1px solid #ccc; padding: 8px; }
    input, select { width: 100%; padding: 6px; box-sizing: border-box; }
    .btn { background: #28a745; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; }
    .btn:hover { background: #218838; }
    .btn-del { background: #dc3545; }
    .btn-del:hover { background: #c82333; }
    .home { background: #007bff; margin-bottom: 10px; text-decoration: none; display: inline-block; padding: 8px 12px; color: white; border-radius: 5px; }
    .home:hover { background: #0056b3; }
  </style>
</head>
<body>
  <h1>👨‍⚕️ 醫師管理</h1>
  <a href="home.php" class="home">🏠 返回首頁</a>

  <?= $message ?>

  <!-- 搜尋表單 -->
  <form method="get">
    <h3>🔍 搜尋醫師</h3>
    <label>姓名關鍵字：<input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>"></label>
    <label>科別：
      <select name="search_specialty">
        <option value="">全部</option>
        <?php foreach ($specialties as $s): ?>
          <option value="<?= $s['specialty_id'] ?>" <?= $search_specialty == $s['specialty_id'] ? 'selected' : '' ?>>
            <?= $s['specialty_id'] ?> - <?= $s['specialty_name'] ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <input type="submit" value="搜尋" class="btn">
  </form>

  <!-- 醫師表單（新增/編輯） -->
  <form method="post">
    <h3><?= isset($_GET["edit"]) ? "✏️ 編輯醫師" : "➕ 新增醫師" ?></h3>
    <?php
    $edit_data = null;
    if (isset($_GET["edit"])) {
      $edit_id = $_GET["edit"];
      $res = $conn->query("SELECT * FROM Doctor WHERE doctor_id = '$edit_id'");
      if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
      }
    }
    ?>
    <label>醫師編號：<input type="text" name="doctor_id" value="<?= $edit_data["doctor_id"] ?? "" ?>" <?= isset($_GET["edit"]) ? "readonly" : "required" ?>></label>
    <label>姓名：<input type="text" name="doctor_name" value="<?= $edit_data["doctor_name"] ?? "" ?>" required></label>
    <label>性別：<input type="text" name="doctor_sex" value="<?= $edit_data["doctor_sex"] ?? "" ?>" required></label>
    <label>電話：<input type="text" name="doctor_phone" value="<?= $edit_data["doctor_phone"] ?? "" ?>" required></label>
    <label>地址：<input type="text" name="doctor_address" value="<?= $edit_data["doctor_address"] ?? "" ?>" required></label>
    <label>生日：<input type="date" name="doctor_birthday" value="<?= $edit_data["doctor_birthday"] ?? "" ?>" required></label>
    <label>到職日：<input type="date" name="doctor_start_date" value="<?= $edit_data["doctor_start_date"] ?? "" ?>" required></label>
    <label>科別：
      <select name="specialty_no" required>
        <?php foreach ($specialties as $s): ?>
          <option value="<?= $s['specialty_id'] ?>" <?= (isset($edit_data["specialty_no"]) && $edit_data["specialty_no"] == $s['specialty_id']) ? "selected" : "" ?>>
            <?= $s['specialty_id'] ?> - <?= $s['specialty_name'] ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if (isset($_GET["edit"])): ?>
      <input type="hidden" name="edit_mode" value="1">
    <?php endif; ?>
    <input type="submit" class="btn" value="<?= isset($_GET["edit"]) ? "更新醫師" : "新增醫師" ?>">
  </form>

  <!-- 醫師列表 -->
  <h3>📋 醫師列表</h3>
  <table>
    <tr>
      <th>ID</th><th>姓名</th><th>性別</th><th>電話</th><th>地址</th><th>生日</th><th>到職日</th><th>科別ID</th><th>科別名稱</th><th>操作</th>
    </tr>
    <?php while ($row = $doctor_list->fetch_assoc()): ?>
      <tr>
        <td><?= $row['doctor_id'] ?></td>
        <td><?= $row['doctor_name'] ?></td>
        <td><?= $row['doctor_sex'] ?></td>
        <td><?= $row['doctor_phone'] ?></td>
        <td><?= $row['doctor_address'] ?></td>
        <td><?= $row['doctor_birthday'] ?></td>
        <td><?= $row['doctor_start_date'] ?></td>
        <td><?= $row['specialty_no'] ?></td>
        <td><?= $row['specialty_name'] ?></td>
        <td>
          <a href="?edit=<?= $row['doctor_id'] ?>" class="btn">編輯</a>
          <a href="?delete=<?= $row['doctor_id'] ?>" class="btn btn-del" onclick="return confirm('確定要刪除嗎？')">刪除</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
<?php $conn->close(); ?>
