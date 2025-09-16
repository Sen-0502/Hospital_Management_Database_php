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

$message = "";

// 刪除科別
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $stmt = $conn->prepare("DELETE FROM Specialty WHERE specialty_id = ?");
  $stmt->bind_param("s", $id);
  if ($stmt->execute()) {
    $message = "<p style='color: green;'>✅ 科別已刪除！</p>";
  } else {
    $message = "<p style='color: red;'>❌ 刪除失敗（可能已有關聯資料）！</p>";
  }
  $stmt->close();
}

// 新增 / 編輯
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_POST["specialty_id"];
  $name = $_POST["specialty_name"];
  $phone = $_POST["specialty_phone"];
  $address = $_POST["specialty_address"];

  if (isset($_POST["edit_mode"])) {
    $stmt = $conn->prepare("UPDATE Specialty SET specialty_name=?, specialty_phone=?, specialty_address=? WHERE specialty_id=?");
    $stmt->bind_param("ssss", $name, $phone, $address, $id);
    $stmt->execute();
    $stmt->close();
    $message = "<p style='color: green;'>✅ 科別資料已更新！</p>";
  } else {
    $check = $conn->prepare("SELECT * FROM Specialty WHERE specialty_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $message = "<p style='color: red;'>❌ 科別 ID 已存在！</p>";
    } else {
      $stmt = $conn->prepare("INSERT INTO Specialty VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $id, $name, $phone, $address);
      $stmt->execute();
      $stmt->close();
      $message = "<p style='color: green;'>✅ 科別新增成功！</p>";
    }
    $check->close();
  }
}

// 搜尋
$search_term = $_GET['search'] ?? '';
$where_clause = $search_term ? "WHERE specialty_name LIKE '%" . $conn->real_escape_string($search_term) . "%' OR specialty_phone LIKE '%" . $conn->real_escape_string($search_term) . "%'" : "";
$specialties = $conn->query("SELECT * FROM Specialty $where_clause ORDER BY specialty_id");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>科別管理</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    form, table { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
    input { width: 100%; padding: 6px; margin: 5px 0; box-sizing: border-box; }
    .btn { background: #28a745; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background: #218838; }
    .btn-del { background: #dc3545; }
    .btn-del:hover { background: #c82333; }
    .home { background: #007bff; margin-bottom: 10px; text-decoration: none; display: inline-block; padding: 8px 12px; color: white; border-radius: 5px; }
    .home:hover { background: #0056b3; }
  </style>
</head>
<body>

  <h1>🏥 科別管理</h1>
  <a href="home.php" class="home">🏠 返回首頁</a>

  <?= $message ?>

  <!-- 搜尋功能 -->
  <form method="get">
    <h3>🔍 搜尋科別</h3>
    <label>名稱/電話：
      <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>">
    </label>
    <input type="submit" value="搜尋" class="btn">
  </form>

  <!-- 新增 / 編輯科別 -->
  <form method="post">
    <h3><?= isset($_GET["edit"]) ? "✏️ 編輯科別" : "➕ 新增科別" ?></h3>
    <?php
    $edit_data = null;
    if (isset($_GET["edit"])) {
      $edit_id = $_GET["edit"];
      $res = $conn->query("SELECT * FROM Specialty WHERE specialty_id = '$edit_id'");
      if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
      }
    }
    ?>
    <label>科別 ID：
      <input type="text" name="specialty_id" value="<?= $edit_data["specialty_id"] ?? "" ?>" <?= isset($_GET["edit"]) ? "readonly" : "required" ?>>
    </label>
    <label>科別名稱：
      <input type="text" name="specialty_name" value="<?= $edit_data["specialty_name"] ?? "" ?>" required>
    </label>
    <label>電話：
      <input type="text" name="specialty_phone" value="<?= $edit_data["specialty_phone"] ?? "" ?>" required>
    </label>
    <label>地址：
      <input type="text" name="specialty_address" value="<?= $edit_data["specialty_address"] ?? "" ?>" required>
    </label>
    <?php if (isset($_GET["edit"])): ?>
      <input type="hidden" name="edit_mode" value="1">
    <?php endif; ?>
    <input type="submit" class="btn" value="<?= isset($_GET["edit"]) ? "更新科別" : "新增科別" ?>">
  </form>

  <!-- 科別列表 -->
  <h3>📋 科別列表</h3>
  <table>
    <tr>
      <th>ID</th><th>名稱</th><th>電話</th><th>地址</th><th>操作</th>
    </tr>
    <?php while ($row = $specialties->fetch_assoc()): ?>
      <tr>
        <td><?= $row['specialty_id'] ?></td>
        <td><?= $row['specialty_name'] ?></td>
        <td><?= $row['specialty_phone'] ?></td>
        <td><?= $row['specialty_address'] ?></td>
        <td>
          <a href="?edit=<?= $row['specialty_id'] ?>" class="btn">編輯</a>
          <a href="?delete=<?= $row['specialty_id'] ?>" class="btn btn-del" onclick="return confirm('確定要刪除嗎？')">刪除</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body>
</html>
<?php $conn->close(); ?>
