<?php
session_start();

$host = 'localhost';
$db = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === "get_orders" && isset($_POST['table_number'])) {
        $tableNumber = $_POST['table_number'];

        $stmt = $pdo->prepare("SELECT o.Order_ID, o.Status, od.Item_ID, mi.Name, od.Quantity 
                               FROM Orders o 
                               JOIN Order_Details od ON o.Order_ID = od.Order_ID 
                               JOIN Menu_Item mi ON od.Item_ID = mi.Item_ID
                               JOIN Customer c ON o.Customer_ID = c.Customer_ID
                               WHERE c.Name = :table_number AND o.Status = 'active'");
        $stmt->execute(['table_number' => $tableNumber]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($orders) {
            echo json_encode(['success' => true, 'orders' => $orders]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bu masaya ait aktif sipariş bulunmamaktadır.']);
        }
    }

    if ($action === "complete_order" && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];

        $stmt = $pdo->prepare("UPDATE Orders SET Status = 'completed' WHERE Order_ID = :order_id");
        if ($stmt->execute(['order_id' => $orderId])) {
            echo json_encode(['success' => true, 'message' => 'Sipariş tamamlandı olarak işaretlendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sipariş güncellenemedi.']);
        }
    }
}
?>
