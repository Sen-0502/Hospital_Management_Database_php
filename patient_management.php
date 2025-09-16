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

if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

$message = "";

// 刪除病患
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM Patient WHERE patient_id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        $message = "<p class='msg success'>✅ 病患已刪除！</p>";
    } else {
        $message = "<p class='msg error'>❌ 刪除失敗！</p>";
    }
    $stmt->close();
}

// 新增或編輯病患
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["patient_id"];
    $name = $_POST["patient_name"];
    $sex = $_POST["patient_sex"];
    $phone = $_POST["patient_phone"];
    $address = $_POST["patient_address"];
    $birthday = $_POST["patient_birthday"];

    if (isset($_POST["edit_mode"])) {
        $stmt = $conn->prepare("UPDATE Patient 
                                SET patient_name=?, patient_sex=?, patient_phone=?, patient_address=?, patient_birthday=? 
                                WHERE patient_id=?");
        $stmt->bind_param("ssssss", $name, $sex, $phone, $address, $birthday, $id);
        $stmt->execute();
        $stmt->close();
        $message = "<p class='msg success'>✅ 病患資料已更新！</p>";
    } else {
        $check = $conn->prepare("SELECT * FROM Patient WHERE patient_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "<p class='msg error'>❌ 病患 ID 已存在！</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO Patient VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $id, $name, $sex, $phone, $address, $birthday);
            $stmt->execute();
            $stmt->close();
            $message = "<p class='msg success'>✅ 病患新增成功！</p>";
        }
        $check->close();
    }
}

// 搜尋
$search_name = $_GET['search_name'] ?? '';
$where_clause = $search_name ? "WHERE patient_name LIKE '%" . $conn->real_escape_string($search_name) . "%'" : "";
$patient_list = $conn->query("SELECT * FROM Patient $where_clause ORDER BY patient_id");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>病患管理</title>
<style>
  body {
    font-family: Arial, sans-serif;
    padding: 30px;
    background-color: #f4f4f4;
  }
  h1 {
    color: #333;
  }
  .top-right {
    position: absolute;
    top: 20px;
    right: 20px;
  }
  .btn {
    background-color: #2c7be5;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    display: inline-block;
    transition: background-color 0.2s ease;
  }
  .btn:hover { background-color: #1a5dc9; }
  .btn-edit { background-color: #28a745; }
  .btn-edit:hover { background-color: #218838; }
  .btn-del { background-color: #dc3545; }
  .btn-del:hover { background-color: #c82333; }
  form, table {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
  }
  table { width: 100%; border-collapse: collapse; }
  th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
  }
  input, select {
    width: 100%;
    padding: 6px;
    margin: 4px 0;
    box-sizing: border-box;
  }
  .msg { font-weight: bold; }
  .success { color: green; }
  .error { color: red; }
</style>
</head>
<body>

<a href="home.php" class="btn top-right">🏠 返回首頁</a>
<h1>🏥 病患管理</h1>

<?= $message ?>

<!-- 搜尋功能 -->
<form method="get">
  <h3>🔍 搜尋病患</h3>
  <label>姓名關鍵字：
    <input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>">
  </label>
  <input type="submit" value="搜尋" class="btn">
</form>

<!-- 病患新增 / 編輯 -->
<form method="post">
  <h3><?= isset($_GET["edit"]) ? "✏️ 編輯病患" : "➕ 新增病患" ?></h3>
  <?php
  $edit_data = null;
  if (isset($_GET["edit"])) {
    $edit_id = $_GET["edit"];
    $res = $conn->query("SELECT * FROM Patient WHERE patient_id = '$edit_id'");
    if ($res->num_rows > 0) {
      $edit_data = $res->fetch_assoc();
    }
  }
  ?>
  <label>病患 ID：
    <input type="text" name="patient_id" value="<?= $edit_data["patient_id"] ?? "" ?>" <?= isset($_GET["edit"]) ? "readonly" : "required" ?>>
  </label>
  <label>姓名：
    <input type="text" name="patient_name" value="<?= $edit_data["patient_name"] ?? "" ?>" required>
  </label>
  <label>性別：
    <input type="text" name="patient_sex" value="<?= $edit_data["patient_sex"] ?? "" ?>" required>
  </label>
  <label>電話：
    <input type="text" name="patient_phone" value="<?= $edit_data["patient_phone"] ?? "" ?>" required>
  </label>
  <label>地址：
    <input type="text" name="patient_address" value="<?= $edit_data["patient_address"] ?? "" ?>" required>
  </label>
  <label>生日：
    <input type="date" name="patient_birthday" value="<?= $edit_data["patient_birthday"] ?? "" ?>" required>
  </label>
  <?php if (isset($_GET["edit"])): ?>
    <input type="hidden" name="edit_mode" value="1">
  <?php endif; ?>
  <input type="submit" class="btn" value="<?= isset($_GET["edit"]) ? "更新病患" : "新增病患" ?>">
</form>

<!-- 病患列表 -->
<h3>📋 病患列表</h3>
<table>
  <tr>
    <th>ID</th><th>姓名</th><th>性別</th><th>電話</th><th>地址</th><th>生日</th><th>操作</th>
  </tr>
  <?php while ($row = $patient_list->fetch_assoc()): ?>
    <tr>
      <td><?= $row['patient_id'] ?></td>
      <td><?= $row['patient_name'] ?></td>
      <td><?= $row['patient_sex'] ?></td>
      <td><?= $row['patient_phone'] ?></td>
      <td><?= $row['patient_address'] ?></td>
      <td><?= $row['patient_birthday'] ?></td>
      <td>
        <a href="?edit=<?= $row['patient_id'] ?>" class="btn btn-edit">編輯</a>
        <a href="?delete=<?= $row['patient_id'] ?>" class="btn btn-del" onclick="return confirm('確定要刪除嗎？')">刪除</a>
      </td>
    </tr>
  <?php endwhile; ?>
</table>

</body>
</html>
<?php $conn->close(); ?>
