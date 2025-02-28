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
    die("<p class='text-danger text-center'>Veritabanı Hatası: " . $e->getMessage() . "</p>");
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'get_orders' && isset($_POST['table_number'])) {
        $tableNumber = $_POST['table_number'];

        try {
            // Sipariş detaylarını alırken 'order_details' ve 'menu_item' tablosunu kullanıyoruz
            $stmt = $pdo->prepare("SELECT o.Order_ID, m.Name, od.Quantity, m.Price
                                   FROM Orders o
                                   JOIN order_details od ON o.Order_ID = od.Order_ID
                                   JOIN menu_item m ON od.Item_ID = m.item_id
                                   WHERE o.Customer_ID IN (SELECT Customer_ID FROM Customer WHERE Name = :name)
                                   AND o.Status = 'completed' AND o.odeme = 'odenmedi'");
            $stmt->bindParam(':name', $tableNumber, PDO::PARAM_STR);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                echo json_encode(['success' => true, 'orders' => $orders]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bu masada sipariş bulunmamaktadır.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }

    if ($action == 'complete_order' && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];

        try {
            // Siparişin ödenmiş olarak işaretlenmesi
            $stmt = $pdo->prepare("UPDATE Orders SET odeme = 'odendi' WHERE Order_ID = :order_id");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Sipariş ödendi olarak işaretlendi.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }
}
?>
