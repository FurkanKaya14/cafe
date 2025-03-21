<?php
session_start();

error_reporting(0);
ini_set('display_errors', 0);

$host = 'localhost';
$db = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => "Veritabanı Hatası: " . $e->getMessage()]));  
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Siparişleri Getir
    if ($action == 'get_orders' && isset($_POST['table_number'])) {
        $tableNumber = $_POST['table_number'];

        try {
            $stmt = $pdo->prepare("
                SELECT o.Order_ID, m.Name, SUM(od.Quantity) AS Quantity, m.Price, m.item_id AS Item_ID
                FROM Orders o
                JOIN order_details od ON o.Order_ID = od.Order_ID
                JOIN menu_item m ON od.Item_ID = m.item_id
                WHERE o.Customer_ID IN (SELECT Customer_ID FROM Customer WHERE Name = :name)
                AND o.Status = 'completed' AND o.odeme = 'odenmedi'
                GROUP BY o.Order_ID, m.item_id
            ");
            $stmt->bindParam(':name', $tableNumber, PDO::PARAM_STR);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }

    // Ödeme Tamamla
    if ($action == 'complete_order') {
        if (!isset($_POST['order_id']) || !isset($_POST['items']) || empty($_POST['items'])) {
            echo json_encode(['success' => false, 'message' => "Eksik veri gönderildi."]);
            exit;
        }

        $orderId = intval($_POST['order_id']);
        $items = json_decode($_POST['items'], true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => "Geçersiz veri formatı."]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            foreach ($items as $item) {
                if (!isset($item['item_id'], $item['pay_quantity'])) {
                    throw new Exception("Eksik sipariş bilgisi.");
                }

                $itemId = intval($item['item_id']);
                $payQuantity = intval($item['pay_quantity']);

                if ($payQuantity <= 0) continue;

                // Miktarı güncelle
                $stmt = $pdo->prepare("UPDATE order_details SET Quantity = GREATEST(Quantity - :pay_quantity, 0) WHERE Order_ID = :order_id AND Item_ID = :item_id");
                $stmt->bindParam(':pay_quantity', $payQuantity, PDO::PARAM_INT);
                $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->bindParam(':item_id', $itemId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Siparişin tamamen ödenip ödenmediğini kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE Order_ID = :order_id AND Quantity > 0");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Sipariş tamamen ödendi, ödeme durumunu güncelle
                $stmt = $pdo->prepare("UPDATE Orders SET odeme = 'odendi' WHERE Order_ID = :order_id");
                $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Güncel sipariş detaylarını çek
            $stmt = $pdo->prepare("
                SELECT m.Name, od.Quantity, m.Price
                FROM order_details od
                JOIN menu_item m ON od.Item_ID = m.item_id
                WHERE od.Order_ID = :order_id
            ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $updatedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Ödeme başarıyla tamamlandı.", 'updatedItems' => $updatedItems], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }

    // Hesabı Kapat
    if ($action == 'close_bill') {
        if (!isset($_POST['order_id'])) {
            echo json_encode(['success' => false, 'message' => "Eksik veri gönderildi."]);
            exit;
        }
    
        $orderId = intval($_POST['order_id']);
    
        try {
            // Siparişin ödenmiş durumunu güncelle
            $stmt = $pdo->prepare("UPDATE Orders SET odeme = 'odendi' WHERE Order_ID = :order_id");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
    
            // Masanın adını al
            $stmt = $pdo->prepare("SELECT c.Name FROM Orders o JOIN Customer c ON o.Customer_ID = c.Customer_ID WHERE o.Order_ID = :order_id");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $tableName = $stmt->fetchColumn();
    
            // JSON yanıtı gönder
            echo json_encode(['success' => true, 'message' => "Hesap başarıyla kapatıldı.", 'table_number' => $tableName]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }
}
?>
