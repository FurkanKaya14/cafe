<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

// POST isteğiyle sipariş oluşturuluyorsa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_number'])) {
    $tableNumber = trim($_POST['table_number']);

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        die("Sepetiniz boş, lütfen ürün ekleyin.");
    }

    try {
        // 1. Müşteri kaydı oluştur
        $stmt = $pdo->prepare("INSERT INTO Customer (Name) VALUES (:table_number)");
        $stmt->execute(['table_number' => "Masa $tableNumber"]);
        $customerId = $pdo->lastInsertId();

        // 2. Sipariş toplam tutarını hesapla
        $totalAmount = 0;
        foreach ($_SESSION['cart'] as $product) {
            $totalAmount += $product['price'] * $product['quantity'];
        }

        // 3. Siparişi Orders tablosuna ekle
        $stmt = $pdo->prepare("INSERT INTO Orders (Customer_ID, Total_Amount, Status) VALUES (:customer_id, :total_amount, 'active')");
        $stmt->execute(['customer_id' => $customerId, 'total_amount' => $totalAmount]);
        $orderId = $pdo->lastInsertId();

        // 4. Sepetteki ürünleri Order_Details tablosuna ekle
        foreach ($_SESSION['cart'] as $productId => $product) {
            $stmt = $pdo->prepare("INSERT INTO Order_Details (Order_ID, Item_ID, Quantity) VALUES (:order_id, :item_id, :quantity)");
            $stmt->execute([
                'order_id' => $orderId,
                'item_id' => $productId,
                'quantity' => $product['quantity']
            ]);
        }

        // 5. Sepeti temizle
        unset($_SESSION['cart']);
        echo "Sipariş başarıyla oluşturuldu! Sipariş ID: $orderId";

    } catch (PDOException $e) {
        die("Sipariş oluşturulurken hata oluştu: " . $e->getMessage());
    }
}
?>
