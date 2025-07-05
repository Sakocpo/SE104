<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$table_id = $input['table_id'] ?? null;
$items = $input['items'] ?? [];

if (empty($items)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No items in order']);
    exit();
}

$connection->begin_transaction();

try {
    $stmt = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $table = $result->fetch_assoc();
    $stmt->close();

    $order_id = null;
    
    if (!empty($table['current_order_id'])) {
        $order_id = $table['current_order_id'];
        $updTs = $connection->prepare("
         UPDATE orders
            SET created_at = NOW()
          WHERE id = ?
       ");
       $updTs->bind_param("i", $order_id);
       $updTs->execute();
       $updTs->close();
    } else {
        $stmt = $connection->prepare("
            INSERT INTO orders (table_id, status, created_at, created_by)
            VALUES (?, 'pending', NOW(), ?)
        ");
        $stmt->bind_param("ii", $table_id, $_SESSION['user']['id']);
        $stmt->execute();
        $order_id = $connection->insert_id;
        $stmt->close();

        $stmt = $connection->prepare("
            UPDATE tables 
            SET current_order_id = ?,
                status = 'occupied'
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $order_id, $table_id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $connection->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, options, note)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $options_json = json_encode($item['options']);
        $note = isset($item['note']) ? trim($item['note']) : '';
        $stmt->bind_param("iiiss", 
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $options_json,
            $note
        );
        $stmt->execute();
    }
    $stmt->close();


    $stmt = $connection->prepare("SELECT table_name FROM tables WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $table_name = "Unknown";
    if ($row = $result->fetch_assoc()) {
        $table_name = $row['table_name'];
    }
    $stmt->close();

    $connection->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'table' => $table_name
    ]);

} catch (Exception $e) {
    $connection->rollback();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
