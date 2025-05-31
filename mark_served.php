<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id'] ?? 0);
$items = $data['items'] ?? [];

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    // Start transaction
    $connection->begin_transaction();

    if (empty($items)) {
        // If no specific items provided, mark all items as served (backward compatibility)
        $stmt = $connection->prepare("
            UPDATE order_items 
            SET served = 1 
            WHERE order_id = ? AND served = 0
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Mark only specific items as served
        foreach ($items as $item) {
            $stmt = $connection->prepare("
                UPDATE order_items oi
                JOIN products p ON oi.product_id = p.id
                SET oi.served = 1 
                WHERE oi.order_id = ? 
                AND p.name = ?
                AND oi.served = 0
            ");
            $stmt->bind_param("is", $order_id, $item['product']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Check if all items are now served
    $stmt = $connection->prepare("
        SELECT COUNT(*) as total, SUM(CASE WHEN served = 1 THEN 1 ELSE 0 END) as served
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Only update table status if ALL items are served
    if ($result['total'] > 0 && $result['total'] === $result['served']) {
        $stmt = $connection->prepare("
            UPDATE tables 
            SET status = 'served' 
            WHERE current_order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    }

    $connection->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
