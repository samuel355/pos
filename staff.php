<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_staff"])) {
  $username = $_POST["username"] ?? "";
  $password = password_hash($_POST["password"] ?? "staff123", PASSWORD_DEFAULT);
  $full_name = $_POST["full_name"] ?? "";
  $email = $_POST["email"] ?? "";
  $phone = $_POST["phone"] ?? "";

  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'staff')");
    $stmt->execute([$username, $password]);
    $user_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare(
      "INSERT INTO staff (user_id, full_name, email, phone) VALUES (?, ?, ?, ?)",
    );
    $stmt->execute([$user_id, $full_name, $email, $phone]);
    $pdo->commit();
    $message = "Staff member added successfully!";
  } catch (Exception $e) {
    $pdo->rollBack();
    $message = "Error adding staff: " . $e->getMessage();
  }
}

$stmt = $pdo->query(
  "SELECT s.*, u.username FROM staff s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC",
);
$staff_list = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Staff Management</h4>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add New Staff</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Default: staff123">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <button type="submit" name="add_staff" class="btn btn-primary w-100">Add Staff</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Staff List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s["full_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($s["username"]); ?></td>
                                    <td><?php echo htmlspecialchars($s["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($s["phone"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
