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
    die(json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // Aktif siparişleri al
    if ($action === "get_orders" && isset($_POST['table_number'])) {
        $tableNumber = $_POST['table_number'];

        try {
            $stmt = $pdo->prepare("SELECT o.Order_ID, o.Status, od.Item_ID, mi.Name, od.Quantity 
                                    FROM Orders o 
                                    JOIN Order_Details od ON o.Order_ID = od.Order_ID 
                                    JOIN Menu_Item mi ON od.Item_ID = mi.Item_ID
                                    JOIN Customer c ON o.Customer_ID = c.Customer_ID
                                    WHERE c.Name = :table_number AND o.Status = 'active'
                                    ORDER BY o.Order_ID");

            $stmt->execute(['table_number' => $tableNumber]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                echo json_encode(['success' => true, 'orders' => $orders]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bu masaya ait aktif sipariş bulunmamaktadır.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı sorgu hatası: ' . $e->getMessage()]);
        }
    }

    // Siparişi tamamla
    if ($action === "complete_item" && isset($_POST['order_id']) && isset($_POST['item_id'])) {
        $orderId = $_POST['order_id'];
        $itemId = $_POST['item_id'];

        try {
            // Orders ve Order_Details tablolarını birleştirerek siparişi tamamla
            $stmt = $pdo->prepare("UPDATE Orders o
                                    JOIN Order_Details od ON o.Order_ID = od.Order_ID
                                    SET o.Status = 'completed'
                                    WHERE o.Order_ID = :order_id AND od.Item_ID = :item_id");

            if ($stmt->execute(['order_id' => $orderId, 'item_id' => $itemId])) {
                echo json_encode(['success' => true, 'message' => 'Ürün tamamlandı olarak işaretlendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ürün güncellenemedi.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı sorgu hatası: ' . $e->getMessage()]);
        }
    }

    // Tüm siparişleri tamamla
    if ($action === "complete_order" && isset($_POST['table_number'])) {
        $tableNumber = $_POST['table_number'];

        try {
            // İlgili masaya ait tüm aktif siparişleri tamamla
            $stmt = $pdo->prepare("UPDATE Orders o
                                    JOIN Customer c ON o.Customer_ID = c.Customer_ID
                                    SET o.Status = 'completed'
                                    WHERE c.Name = :table_number AND o.Status = 'active'");

            if ($stmt->execute(['table_number' => $tableNumber])) {
                echo json_encode(['success' => true, 'message' => 'Tüm siparişler tamamlandı.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Siparişler tamamlanamadı.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı sorgu hatası: ' . $e->getMessage()]);
        }
    }
}
?>
