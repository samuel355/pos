<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../includes/table_packages.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login again.']);
    exit();
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

ensureTablePackageSchema($pdo);

function tableStatsQuery()
{
    return "
        SELECT
            rt.id,
            rt.name,
            rt.status,
            rt.reserved_by,
            rt.reserved_at,
            rt.serving_user_id,
            rt.serve_status,
            rt.created_at,
            u.username AS serving_username,
            tb.id AS booking_id,
            tb.customer_name AS booking_customer_name,
            tb.customer_contact AS booking_customer_contact,
            tb.booked_at AS booking_booked_at,
            tp.id AS booking_package_id,
            tp.name AS booking_package_name,
            tp.price AS booking_package_price,
            tp.tier AS booking_package_tier,
            COALESCE(stats.order_count, 0) AS order_count,
            COALESCE(stats.total_amount, 0) AS total_amount
        FROM restaurant_tables rt
        LEFT JOIN users u ON u.id = rt.serving_user_id
        LEFT JOIN table_bookings tb ON tb.table_id = rt.id AND tb.status = 'open'
        LEFT JOIN table_packages tp ON tp.id = tb.package_id
        LEFT JOIN (
            SELECT
                table_id,
                COUNT(*) AS order_count,
                SUM(final_amount) AS total_amount
            FROM sales
            WHERE table_id IS NOT NULL
              AND DATE(created_at) = CURDATE()
            GROUP BY table_id
        ) stats ON stats.table_id = rt.id
    ";
}

function fetchTableById(PDO $pdo, int $tableId)
{
    $fetch = $pdo->prepare(tableStatsQuery() . ' WHERE rt.id = ?');
    $fetch->execute([$tableId]);
    return $fetch->fetch();
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(tableStatsQuery() . " ORDER BY rt.id ASC");
        $tables = $stmt->fetchAll();

        echo json_encode(['success' => true, 'tables' => $tables]);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $action = isset($data['action']) ? trim((string)$data['action']) : '';

    if ($method === 'POST' && $action === 'book') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;
        $packageId = isset($data['package_id']) ? (int)$data['package_id'] : 0;
        $customerName = trim((string)($data['customer_name'] ?? ''));
        $customerContact = trim((string)($data['customer_contact'] ?? ''));

        if ($tableId <= 0 || $packageId <= 0) {
            throw new Exception('Select a valid table and package.');
        }

        if ($customerName === '') {
            throw new Exception('Customer name is required.');
        }

        if (mb_strlen($customerName) > 120) {
            throw new Exception('Customer name must be 120 characters or fewer.');
        }

        $openCheck = $pdo->prepare("SELECT COUNT(*) FROM table_bookings WHERE table_id = ? AND status = 'open'");
        $openCheck->execute([$tableId]);

        if ((int)$openCheck->fetchColumn() > 0) {
            throw new Exception('This table already has an open booking.');
        }

        $pdo->beginTransaction();

        try {
            $saleId = createPackageSale($pdo, $tableId, $packageId, $userId);

            $bookingStmt = $pdo->prepare("
                INSERT INTO table_bookings
                    (table_id, package_id, sale_id, customer_name, customer_contact, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $bookingStmt->execute([
                $tableId,
                $packageId,
                $saleId,
                $customerName,
                $customerContact,
                $userId,
            ]);

            $reserveStmt = $pdo->prepare("
                UPDATE restaurant_tables
                SET reserved_by = ?, customer_contact = ?, reserved_at = NOW(), serving_user_id = ?, serve_status = 'serving'
                WHERE id = ?
            ");
            $reserveStmt->execute([$customerName, $customerContact, $userId, $tableId]);

            $pdo->commit();
        } catch (Exception $inner) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $inner;
        }

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'sale_id' => $saleId,
            'message' => 'Table booked and package sale created.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'close') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $stmt = $pdo->prepare("SELECT id, table_id, booked_at FROM table_bookings WHERE table_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$tableId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new Exception('This table has no open booking.');
        }

        $pdo->beginTransaction();

        try {
            // Snapshot every sale tied to this booking (the package sale plus every
            // drink round added while serving) before we unlink them, so the final
            // combined bill can still be printed after the table is closed.
            $saleIdsStmt = $pdo->prepare("
                SELECT id
                FROM sales
                WHERE table_id = ?
                  AND created_at >= ?
                ORDER BY created_at ASC
            ");
            $saleIdsStmt->execute([$tableId, $booking['booked_at']]);
            $closedSaleIds = array_map('intval', array_column($saleIdsStmt->fetchAll(), 'id'));

            $close = $pdo->prepare("UPDATE table_bookings SET status = 'closed', closed_at = NOW() WHERE id = ?");
            $close->execute([(int)$booking['id']]);

            $unlinkSales = $pdo->prepare('
                UPDATE sales
                SET table_id = NULL
                WHERE table_id = ?
                  AND created_at >= ?
            ');
            $unlinkSales->execute([$tableId, $booking['booked_at']]);

            $resetTable = $pdo->prepare("
                UPDATE restaurant_tables
                SET reserved_by = NULL,
                    customer_contact = NULL,
                    reserved_at = NULL,
                    serving_user_id = NULL,
                    serve_status = 'none'
                WHERE id = ?
            ");
            $resetTable->execute([$tableId]);

            $pdo->commit();
        } catch (Exception $inner) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $inner;
        }

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'sale_ids' => $closedSaleIds,
            'message' => 'Table closed. Final bill is ready.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'serve') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $check = $pdo->prepare('SELECT status FROM restaurant_tables WHERE id = ?');
        $check->execute([$tableId]);
        $table = $check->fetch();

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if (strcasecmp((string)$table['status'], 'Active') !== 0) {
            throw new Exception('Table is inactive.');
        }

        $stmt = $pdo->prepare("
            UPDATE restaurant_tables
            SET serving_user_id = ?, serve_status = 'serving'
            WHERE id = ?
        ");
        $stmt->execute([$userId, $tableId]);

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'You are now serving this table.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'mark_ready') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $check = $pdo->prepare('SELECT serving_user_id, serve_status FROM restaurant_tables WHERE id = ?');
        $check->execute([$tableId]);
        $table = $check->fetch();

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if ($table['serve_status'] === 'none') {
            throw new Exception('This table is not being served yet.');
        }

        if ((int)$table['serving_user_id'] !== $userId && !$isAdmin) {
            throw new Exception('Only the assigned server can mark this table ready.');
        }

        $stmt = $pdo->prepare("
            UPDATE restaurant_tables
            SET serve_status = 'ready'
            WHERE id = ?
        ");
        $stmt->execute([$tableId]);

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'Table marked ready to serve.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'clear_serve') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $check = $pdo->prepare('SELECT serving_user_id FROM restaurant_tables WHERE id = ?');
        $check->execute([$tableId]);
        $table = $check->fetch();

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if ((int)$table['serving_user_id'] !== $userId && !$isAdmin) {
            throw new Exception('Only the assigned server or an admin can clear serving status.');
        }

        $stmt = $pdo->prepare("
            UPDATE restaurant_tables
            SET serving_user_id = NULL, serve_status = 'none'
            WHERE id = ?
        ");
        $stmt->execute([$tableId]);

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'Serving status cleared.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'finish_service') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $check = $pdo->prepare('SELECT serving_user_id, serve_status FROM restaurant_tables WHERE id = ?');
        $check->execute([$tableId]);
        $table = $check->fetch();

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if ((int)$table['serving_user_id'] !== $userId && !$isAdmin) {
            throw new Exception('Only the assigned server or an admin can finish service.');
        }

        $pdo->beginTransaction();

        try {
            // Clear today's table links so the table goes back to default state
            // while preserving sale history.
            $clearSales = $pdo->prepare("
                UPDATE sales
                SET table_id = NULL
                WHERE table_id = ?
                  AND DATE(created_at) = CURDATE()
            ");
            $clearSales->execute([$tableId]);

            $resetTable = $pdo->prepare("
                UPDATE restaurant_tables
                SET reserved_by = NULL,
                    reserved_at = NULL,
                    serving_user_id = NULL,
                    serve_status = 'none'
                WHERE id = ?
            ");
            $resetTable->execute([$tableId]);

            $pdo->commit();
        } catch (Exception $inner) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $inner;
        }

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'Service finished. Table reset to default state.',
        ]);
        exit();
    }

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Only administrators can manage tables.']);
        exit();
    }

    if ($method === 'POST' && $action === 'create') {
        $name = trim((string)($data['name'] ?? ''));
        $status = in_array($data['status'] ?? 'Active', ['Active', 'Inactive'], true) ? $data['status'] : 'Active';

        if ($name === '') {
            throw new Exception('Table name is required.');
        }

        if (mb_strlen($name) > 100) {
            throw new Exception('Table name must be 100 characters or fewer.');
        }

        $check = $pdo->prepare('SELECT id FROM restaurant_tables WHERE name = ? LIMIT 1');
        $check->execute([$name]);

        if ($check->fetch()) {
            throw new Exception('A table with this name already exists.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO restaurant_tables (name, status)
            VALUES (?, ?)
        ');
        $stmt->execute([$name, $status]);

        $tableId = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'Table created successfully.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'update') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;
        $name = trim((string)($data['name'] ?? ''));
        $status = in_array($data['status'] ?? 'Active', ['Active', 'Inactive'], true) ? $data['status'] : 'Active';

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        if ($name === '') {
            throw new Exception('Table name is required.');
        }

        $check = $pdo->prepare('SELECT id FROM restaurant_tables WHERE name = ? AND id != ? LIMIT 1');
        $check->execute([$name, $tableId]);

        if ($check->fetch()) {
            throw new Exception('A table with this name already exists.');
        }

        $stmt = $pdo->prepare('
            UPDATE restaurant_tables
            SET name = ?, status = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $status, $tableId]);

        echo json_encode([
            'success' => true,
            'table' => fetchTableById($pdo, $tableId),
            'message' => 'Table updated successfully.',
        ]);
        exit();
    }

    if ($method === 'POST' && $action === 'delete') {
        $tableId = isset($data['id']) ? (int)$data['id'] : 0;

        if ($tableId <= 0) {
            throw new Exception('Invalid table ID.');
        }

        $stmt = $pdo->prepare('DELETE FROM restaurant_tables WHERE id = ?');
        $stmt->execute([$tableId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Table not found.');
        }

        echo json_encode(['success' => true, 'message' => 'Table deleted successfully.']);
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
