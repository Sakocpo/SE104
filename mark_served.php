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
    $connection->begin_transaction();

    if (empty($items)) {
        $stmt = $connection->prepare("
            UPDATE order_items 
            SET served = 1 
            WHERE order_id = ? AND served = 0
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    } else {
        foreach ($items as $item) {
            $stmt = $connection->prepare("
                UPDATE order_items 
                SET served = 1 
                WHERE order_id = ? 
                AND product_id = ?
                AND served = 0
            ");
            $stmt->bind_param("ii", $order_id, $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $stmt = $connection->prepare("
        SELECT COUNT(*) as total, SUM(CASE WHEN served = 1 THEN 1 ELSE 0 END) as served
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['total'] > 0 && $result['total'] === $result['served']) {
        $stmt = $connection->prepare("
            UPDATE orders 
            SET status = 'served' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();

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
