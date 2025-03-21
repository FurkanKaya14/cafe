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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="admin.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="text-center text-danger mb-4">Aktif Siparişler</h1>
    <div id="orders" class="d-flex flex-wrap justify-content-center gap-3">
        <?php
        try {
            $stmt = $pdo->query("SELECT DISTINCT c.Customer_ID, c.Name 
                                    FROM Orders o 
                                    JOIN Customer c ON o.Customer_ID = c.Customer_ID 
                                    WHERE o.Status = 'active'");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                $masaNumaralari = [];
                foreach ($orders as $order) {
                    if (!in_array($order['Name'], $masaNumaralari)) {
                        $masaNumaralari[] = $order['Name'];
                        echo "<button class='btn btn-outline-danger order-btn' data-table-number='{$order['Name']}'>"
                                . htmlspecialchars($order['Name']) . "</button>";
                    }
                }
            } else {
                echo "<p class='text-center text-muted'>Aktif sipariş bulunmamaktadır.</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='text-danger text-center'>Hata: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <h2 class="text-center text-danger mt-5">Sipariş Detayları</h2>
    <div id="order-details" class="p-4 bg-white shadow rounded mt-3 text-center">
        <p class="text-muted">Lütfen bir sipariş seçin.</p>
    </div>
</div>

<script>
$(document).ready(function() {
    $(document).on("click", ".order-btn", function() {
        var tableNumber = $(this).data("table-number");

        $.post("admin_process.php", { action: "get_orders", table_number: tableNumber }, function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                var html = "<h3 class='text-danger'>" + tableNumber + " Siparişleri</h3>";
                html += "<table class='table table-bordered mt-3'>";
                html += "<thead class='table-dark'><tr><th>Ürün</th><th>Adet</th></tr></thead><tbody>";
                
                data.orders.forEach(function(order) {
                    html += "<tr><td>" + order.Name + "</td><td>" + order.Quantity + "</td></tr>";
                });

                html += "</tbody></table>";
                html += "<button class='btn btn-success complete-order' data-table-number='" + tableNumber + "'>Siparişi Tamamla</button>";
                $("#order-details").html(html);
            } else {
                $("#order-details").html("<p class='text-muted'>" + data.message + "</p>");
            }
        });
    });

    $(document).on("click", ".complete-order", function() {
        var tableNumber = $(this).data("table-number");

        $.post("admin_process.php", { action: "complete_order", table_number: tableNumber }, function(response) {
            var data = JSON.parse(response);
            alert(data.message);
            location.reload();
        });
    });
});
</script>

</body>
</html>
