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
                SELECT od.Order_Detail_ID, o.Order_ID, m.Name, od.Quantity, m.Price, m.item_id AS Item_ID
                FROM Orders o
                JOIN order_details od ON o.Order_ID = od.Order_ID
                JOIN menu_item m ON od.Item_ID = m.item_id
                WHERE o.Customer_ID IN (SELECT Customer_ID FROM Customer WHERE Name = :name)
                AND o.Status = 'completed' AND o.odeme = 'odenmedi'
                ORDER BY od.Order_Detail_ID
            ");
            $stmt->bindParam(':name', $tableNumber, PDO::PARAM_STR);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }

    // Miktarları Güncelle
    
    
    if ($action == 'update_quantities' && isset($_POST['items']) && isset($_POST['order_id'])) {
        $items = json_decode($_POST['items'], true);
        $orderId = intval($_POST['order_id']);
    
        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => "Geçersiz veya boş ürün listesi."]);
            exit;
        }
    
        try {
            $pdo->beginTransaction();
    
            foreach ($items as $item) {
                $orderDetailId = intval($item['order_detail_id']);
                $quantityDifference = intval($item['quantity_difference']);
    
                // Her siparişin miktarını güncelle
                $stmt = $pdo->prepare("
                    UPDATE order_details 
                    SET Quantity = GREATEST(Quantity - :quantity_difference, 0) 
                    WHERE Order_Detail_ID = :order_detail_id
                ");
                $stmt->bindParam(':quantity_difference', $quantityDifference, PDO::PARAM_INT);
                $stmt->bindParam(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
                $stmt->execute();
            }
    
            // Siparişin tamamen ödenip ödenmediğini kontrol et
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) 
                FROM order_details 
                WHERE Order_ID = :order_id 
                AND Quantity > 0
            ");
            $stmtCheck->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $remainingItems = $stmtCheck->fetchColumn();
    
            if ($remainingItems == 0) {
                $stmtUpdateOrder = $pdo->prepare("
                    UPDATE Orders 
                    SET odeme = 'odendi' 
                    WHERE Order_ID = :order_id
                ");
                $stmtUpdateOrder->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $stmtUpdateOrder->execute();
            }
    
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Ürün miktarları güncellendi."]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()]);
        }
    }

    // Hesabı Kapat
    
    if ($action == 'close_bill') {
        if (!isset($_POST['table_number'])) {
            echo json_encode(['success' => false, 'message' => "Eksik veri gönderildi."]);
            exit;
        }
    
        $tableNumber = $_POST['table_number'];
    
        try {
            // Tüm siparişlerin ödeme durumunu güncelle
            $stmt = $pdo->prepare("
                UPDATE Orders 
                SET odeme = 'odendi' 
                WHERE Customer_ID IN (SELECT Customer_ID FROM Customer WHERE Name = :table_number)
            ");
            $stmt->bindParam(':table_number', $tableNumber, PDO::PARAM_STR);
            $stmt->execute();
    
            // JSON yanıtı gönder
            echo json_encode(['success' => true, 'message' => "Hesap başarıyla kapatıldı.", 'table_number' => $tableNumber]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Hata: " . $e->getMessage()]);
        }
    }
}
?>