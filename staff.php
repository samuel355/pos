<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

requireAdmin();

$message = "";
$error = "";

$currentUserRole = $_SESSION["role"] ?? "";
$isAdmin = $currentUserRole === "admin";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function validatePassword($password, $confirmPassword, $required = true) {
    if (!$required && $password === "" && $confirmPassword === "") {
        return "";
    }

    if ($password === "") {
        return "Password is required.";
    }

    if (strlen($password) < 6) {
        return "Password must be at least 6 characters.";
    }

    if ($password !== $confirmPassword) {
        return "Password confirmation does not match.";
    }

    return "";
}

function cleanPhone($phone) {
    return trim((string)$phone);
}

// Handle Add Staff
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_staff"])) {
    try {
        $fullName = trim($_POST["full_name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = cleanPhone($_POST["phone"] ?? "");
        $role = $isAdmin ? trim($_POST["role"] ?? "staff") : "staff";
        $password = (string)($_POST["password"] ?? "");
        $confirmPassword = (string)($_POST["confirm_password"] ?? "");

        if (!in_array($role, ["admin", "staff"], true)) {
            $role = "staff";
        }

        if ($fullName === "") {
            throw new Exception("Full name is required.");
        }

        if ($username === "") {
            throw new Exception("Username is required.");
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            throw new Exception("Username must be 3-50 characters and contain only letters, numbers, dots, underscores, or hyphens.");
        }

        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $passwordError = validatePassword($password, $confirmPassword, true);

        if ($passwordError !== "") {
            throw new Exception($passwordError);
        }

        $checkUsername = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $checkUsername->execute([$username]);

        if ($checkUsername->fetch()) {
            throw new Exception("Username already exists.");
        }

        if ($email !== "") {
            $checkEmail = $pdo->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                throw new Exception("Email already exists.");
            }
        }

        $pdo->beginTransaction();

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users 
                (username, password, role) 
            VALUES 
                (?, ?, ?)
        ");
        $stmt->execute([$username, $hashedPassword, $role]);

        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO staff 
                (user_id, full_name, email, phone) 
            VALUES 
                (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $fullName, $email, $phone]);

        $pdo->commit();

        header("Location: staff.php?created=1");
        exit;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex->getMessage();
    }
}

// Handle Update Staff
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_staff"])) {
    try {
        $staffId = isset($_POST["staff_id"]) ? (int)$_POST["staff_id"] : 0;
        $userId = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;
        $fullName = trim($_POST["edit_full_name"] ?? "");
        $username = trim($_POST["edit_username"] ?? "");
        $email = trim($_POST["edit_email"] ?? "");
        $phone = cleanPhone($_POST["edit_phone"] ?? "");
        $role = $isAdmin ? trim($_POST["edit_role"] ?? "staff") : "staff";
        $password = (string)($_POST["edit_password"] ?? "");
        $confirmPassword = (string)($_POST["edit_confirm_password"] ?? "");

        if (!in_array($role, ["admin", "staff"], true)) {
            $role = "staff";
        }

        if (isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] === $userId && $role !== "admin") {
            throw new Exception("You cannot remove your own admin role.");
        }

        if ($staffId <= 0 || $userId <= 0) {
            throw new Exception("Invalid staff selected.");
        }

        if ($fullName === "") {
            throw new Exception("Full name is required.");
        }

        if ($username === "") {
            throw new Exception("Username is required.");
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            throw new Exception("Username must be 3-50 characters and contain only letters, numbers, dots, underscores, or hyphens.");
        }

        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        $passwordError = validatePassword($password, $confirmPassword, false);

        if ($passwordError !== "") {
            throw new Exception($passwordError);
        }

        $checkUsername = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $checkUsername->execute([$username, $userId]);

        if ($checkUsername->fetch()) {
            throw new Exception("Username already exists.");
        }

        if ($email !== "") {
            $checkEmail = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND id <> ? LIMIT 1");
            $checkEmail->execute([$email, $staffId]);

            if ($checkEmail->fetch()) {
                throw new Exception("Email already exists.");
            }
        }

        $pdo->beginTransaction();

        if ($password !== "") {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE users
                SET username = ?, password = ?, role = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $hashedPassword, $role, $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET username = ?, role = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $role, $userId]);
        }

        $stmt = $pdo->prepare("
            UPDATE staff
            SET full_name = ?, email = ?, phone = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $email, $phone, $staffId]);

        $pdo->commit();

        header("Location: staff.php?updated=1");
        exit;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex->getMessage();
    }
}

// Handle Delete Staff
if (isset($_GET["delete"])) {
    try {
        if (!$isAdmin) {
            throw new Exception("Only admin users can delete staff members.");
        }

        $staffId = (int)$_GET["delete"];

        if ($staffId <= 0) {
            throw new Exception("Invalid staff selected.");
        }

        $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch();

        if (!$staff) {
            throw new Exception("Staff not found.");
        }

        $userId = (int)$staff["user_id"];

        if (isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] === $userId) {
            throw new Exception("You cannot delete your own account.");
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$userId]);

        header("Location: staff.php?deleted=1");
        exit;
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

if (isset($_GET["created"]) && $_GET["created"] === "1") {
    $message = "Staff member added successfully.";
}

if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Staff member updated successfully.";
}

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {
    $message = "Staff member deleted successfully.";
}

$stmt = $pdo->query("
    SELECT 
        s.id,
        s.user_id,
        s.full_name,
        s.email,
        s.phone,
        u.username,
        u.role,
        u.created_at
    FROM staff s
    INNER JOIN users u ON s.user_id = u.id
    WHERE u.role = 'staff'
    ORDER BY s.id DESC
");
$staffList = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

    <div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h5 class="mb-1">Staff Management</h5>
            <p class="text-muted mb-0">Create staff accounts, manage login details, and update staff information.</p>
        </div>
    </div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Add New Staff</h6>
                </div>

                <div class="card-body">
                    <form method="POST" class="vstack gap-3" autocomplete="off">
                        <div>
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Ama Mensah">
                        </div>

                        <div>
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="e.g. ama.mensah">
                            <small class="text-muted">3-50 characters. Letters, numbers, dot, underscore, hyphen.</small>
                        </div>

                        <?php if ($isAdmin): ?>
                            <div>
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control" required>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimum 6 characters">
                        </div>

                        <div>
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Repeat password">
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="staff@example.com">
                        </div>

                        <div>
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="0240000000">
                        </div>

                        <button type="submit" name="add_staff" class="btn btn-primary w-100">
                            <i class="ri-user-add-line me-1"></i>
                            Add Staff
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <h6 class="card-title mb-0">Staff List</h6>
                    <span class="badge bg-light text-muted border">
                    <?php echo count($staffList); ?> Staff
                </span>
                </div>

                <div class="card-body">
                    <?php if (empty($staffList)): ?>
                        <div class="text-center py-5">
                            <div class="avatar size-14 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                <i class="ri-team-line fs-3"></i>
                            </div>
                            <h6 class="mb-1">No staff found</h6>
                            <p class="text-muted mb-0">Add your first staff member using the form.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Staff</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Created</th>
                                    <th style="width: 190px;">Action</th>
                                </tr>
                                </thead>

                                <tbody>
                                <?php foreach ($staffList as $staff): ?>
                                    <tr>
                                        <td>
                                            <h6 class="mb-1 fs-14"><?php echo e($staff["full_name"]); ?></h6>
                                            <span class="text-muted fs-sm">ID: <?php echo (int)$staff["id"]; ?></span>
                                        </td>

                                        <td><?php echo e($staff["username"]); ?></td>

                                        <td>
                                            <?php if ($staff["role"] === "admin"): ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Staff</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?php echo e($staff["email"] ?: "N/A"); ?></td>

                                        <td><?php echo e($staff["phone"] ?: "N/A"); ?></td>

                                        <td><?php echo e($staff["created_at"]); ?></td>

                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button"
                                                        class="btn btn-sm btn-light border view-staff-btn"
                                                        data-staff='<?php echo e(json_encode([
                                                                "id" => (int)$staff["id"],
                                                                "user_id" => (int)$staff["user_id"],
                                                                "full_name" => $staff["full_name"],
                                                                "username" => $staff["username"],
                                                                "role" => $staff["role"],
                                                                "email" => $staff["email"],
                                                                "phone" => $staff["phone"],
                                                                "created_at" => $staff["created_at"],
                                                        ])); ?>'>
                                                    View
                                                </button>

                                                <button type="button"
                                                        class="btn btn-sm btn-primary edit-staff-btn"
                                                        data-staff='<?php echo e(json_encode([
                                                                "id" => (int)$staff["id"],
                                                                "user_id" => (int)$staff["user_id"],
                                                                "full_name" => $staff["full_name"],
                                                                "username" => $staff["username"],
                                                                "role" => $staff["role"],
                                                                "email" => $staff["email"],
                                                                "phone" => $staff["phone"],
                                                        ])); ?>'>
                                                    Edit
                                                </button>

                                                <?php if ($isAdmin): ?>
                                                    <a href="staff.php?delete=<?php echo (int)$staff["id"]; ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Delete this staff member?')">
                                                        Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Staff Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="vstack gap-3">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Full Name</span>
                            <strong id="viewStaffFullName"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Username</span>
                            <strong id="viewStaffUsername"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Role</span>
                            <strong id="viewStaffRole"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Email</span>
                            <strong id="viewStaffEmail"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Phone</span>
                            <strong id="viewStaffPhone"></strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Created</span>
                            <strong id="viewStaffCreated"></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="staff.php" autocomplete="off">
                    <input type="hidden" name="staff_id" id="editStaffId">
                    <input type="hidden" name="user_id" id="editStaffUserId">

                    <div class="modal-header">
                        <h6 class="modal-title">Edit Staff</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="vstack gap-3">
                            <div>
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="edit_full_name" id="editStaffFullName" class="form-control" required>
                            </div>

                            <div>
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="edit_username" id="editStaffUsername" class="form-control" required>
                            </div>

                            <?php if ($isAdmin): ?>
                                <div>
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select name="edit_role" id="editStaffRole" class="form-control" required>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="edit_email" id="editStaffEmail" class="form-control">
                            </div>

                            <div>
                                <label class="form-label">Phone</label>
                                <input type="text" name="edit_phone" id="editStaffPhone" class="form-control">
                            </div>

                            <div class="alert alert-light border mb-0">
                                <strong>Password change</strong>
                                <p class="text-muted mb-0 fs-sm">Leave password fields empty if you do not want to change the password.</p>
                            </div>

                            <div>
                                <label class="form-label">New Password</label>
                                <input type="password" name="edit_password" class="form-control" minlength="6" placeholder="Minimum 6 characters">
                            </div>

                            <div>
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="edit_confirm_password" class="form-control" minlength="6" placeholder="Repeat new password">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_staff" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.view-staff-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const staff = JSON.parse(this.dataset.staff);

                    document.getElementById('viewStaffFullName').innerText = staff.full_name || '';
                    document.getElementById('viewStaffUsername').innerText = staff.username || '';
                    document.getElementById('viewStaffRole').innerText = staff.role === 'admin' ? 'Admin' : 'Staff';
                    document.getElementById('viewStaffEmail').innerText = staff.email || 'N/A';
                    document.getElementById('viewStaffPhone').innerText = staff.phone || 'N/A';
                    document.getElementById('viewStaffCreated').innerText = staff.created_at || 'N/A';

                    new bootstrap.Modal(document.getElementById('viewStaffModal')).show();
                });
            });

            document.querySelectorAll('.edit-staff-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const staff = JSON.parse(this.dataset.staff);

                    document.getElementById('editStaffId').value = staff.id;
                    document.getElementById('editStaffUserId').value = staff.user_id;
                    document.getElementById('editStaffFullName').value = staff.full_name || '';
                    document.getElementById('editStaffUsername').value = staff.username || '';

                    document.getElementById('editStaffPhone').value = staff.phone || '';

                    const editStaffRole = document.getElementById('editStaffRole');
                    if (editStaffRole) {
                        editStaffRole.value = staff.role || 'staff';
                    }

                    document.getElementById('editStaffEmail').value = staff.email || '';

                    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
                });
            });
        });
    </script>

<?php include "includes/footer.php"; ?>
