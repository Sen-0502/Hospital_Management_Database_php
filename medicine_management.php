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

// 刪除
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $stmt = $conn->prepare("DELETE FROM Medicine WHERE medicine_id = ?");
  $stmt->bind_param("s", $id);
  if ($stmt->execute()) {
    $message = "<p style='color: green;'>✅ 藥品已刪除！</p>";
  } else {
    $message = "<p style='color: red;'>❌ 刪除失敗！</p>";
  }
  $stmt->close();
}

// 新增 / 編輯
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_POST["medicine_id"];
  $name = $_POST["medicine_name"];
  $price = $_POST["medicine_price"];
  $qty = $_POST["medicine_quantity"];

  if (isset($_POST["edit_mode"])) {
    $stmt = $conn->prepare("UPDATE Medicine SET medicine_name=?, medicine_price=?, medicine_quantity=? WHERE medicine_id=?");
    $stmt->bind_param("sdis", $name, $price, $qty, $id);
    $stmt->execute();
    $stmt->close();
    $message = "<p style='color: green;'>✅ 藥品資料已更新！</p>";
  } else {
    $check = $conn->prepare("SELECT * FROM Medicine WHERE medicine_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $message = "<p style='color: red;'>❌ 藥品 ID 已存在！</p>";
    } else {
      $stmt = $conn->prepare("INSERT INTO Medicine VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssdi", $id, $name, $price, $qty);
      $stmt->execute();
      $stmt->close();
      $message = "<p style='color: green;'>✅ 藥品新增成功！</p>";
    }
    $check->close();
  }
}

// 搜尋
$search_name = $_GET['search_name'] ?? '';
$where_clause = $search_name ? "WHERE medicine_name LIKE '%" . $conn->real_escape_string($search_name) . "%'" : "";
$medicines = $conn->query("SELECT * FROM Medicine $where_clause ORDER BY medicine_id");
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>藥品管理</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f4f4f4; }
    form, table { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    input, select { width: 100%; padding: 6px 6px; margin: 4px 0; box-sizing: border-box;}
    .btn { background-color: #28a745; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background-color: #218838; }
    .btn-del { background-color: #dc3545; }
    .btn-del:hover { background-color: #c82333; }
    .home { background: #007bff; margin-bottom: 10px; text-decoration: none; display: inline-block; padding: 8px 12px; color: white; border-radius: 5px; }
    .home:hover { background: #0056b3; }
  </style>
</head>
<body>

  <h1>💊 藥品管理</h1>
  <a href="home.php" class="home">🏠 返回首頁</a>

  <?= $message ?>

  <!-- 搜尋藥品 -->
  <form method="get">
    <h3>🔍 搜尋藥品</h3>
    <label>名稱關鍵字：
      <input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>">
    </label>
    <input type="submit" value="搜尋" class="btn">
  </form>

  <!-- 藥品新增 / 編輯 -->
  <form method="post">
    <h3><?= isset($_GET["edit"]) ? "✏️ 編輯藥品" : "➕ 新增藥品" ?></h3>
    <?php
    $edit_data = null;
    if (isset($_GET["edit"])) {
      $edit_id = $_GET["edit"];
      $res = $conn->query("SELECT * FROM Medicine WHERE medicine_id = '$edit_id'");
      if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
      }
    }
    ?>
    <label>藥品 ID：
      <input type="text" name="medicine_id" value="<?= $edit_data["medicine_id"] ?? "" ?>" <?= isset($_GET["edit"]) ? "readonly" : "required" ?>>
    </label>
    <label>藥品名稱：
      <input type="text" name="medicine_name" value="<?= $edit_data["medicine_name"] ?? "" ?>" required>
    </label>
    <label>藥品單價：
      <input type="number" step="0.01" name="medicine_price" value="<?= $edit_data["medicine_price"] ?? "" ?>" required>
    </label>
    <label>庫存數量：
      <input type="number" name="medicine_quantity" value="<?= $edit_data["medicine_quantity"] ?? "" ?>" required>
    </label>
    <?php if (isset($_GET["edit"])): ?>
      <input type="hidden" name="edit_mode" value="1">
    <?php endif; ?>
    <input type="submit" class="btn" value="<?= isset($_GET["edit"]) ? "更新藥品" : "新增藥品" ?>">
  </form>

  <!-- 藥品列表 -->
  <h3>📋 藥品列表</h3>
  <table>
    <tr>
      <th>ID</th><th>名稱</th><th>單價</th><th>庫存</th><th>操作</th>
    </tr>
    <?php while ($row = $medicines->fetch_assoc()): ?>
      <tr>
        <td><?= $row['medicine_id'] ?></td>
        <td><?= $row['medicine_name'] ?></td>
        <td><?= $row['medicine_price'] ?></td>
        <td><?= $row['medicine_quantity'] ?></td>
        <td>
          <a href="?edit=<?= $row['medicine_id'] ?>" class="btn">編輯</a>
          <a href="?delete=<?= $row['medicine_id'] ?>" class="btn btn-del" onclick="return confirm('確定要刪除嗎？')">刪除</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
<?php $conn->close(); ?>
