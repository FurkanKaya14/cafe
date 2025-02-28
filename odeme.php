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
    <title>Ödeme Sayfası</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="text-center text-danger mb-4">Ödenmedi Siparişler</h1>
    <div id="orders" class="d-flex flex-wrap justify-content-center gap-3">
        <?php
        try {
            // Her masanın aktif siparişlerini getirmek için Customer'ın "Name" alanı üzerinden sorgu yapıyoruz
            $stmt = $pdo->query("SELECT DISTINCT c.Customer_ID, c.Name 
                                 FROM Orders o 
                                 JOIN Customer c ON o.Customer_ID = c.Customer_ID 
                                 WHERE o.Status = 'completed' AND o.odeme = 'odenmedi'");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                foreach ($orders as $order) {
                    // Her masa için yalnızca bir buton gösteriyoruz
                    echo "<button class='btn btn-outline-danger order-btn' data-table-number='{$order['Name']}'>
                            Masa: " . htmlspecialchars($order['Name']) . "
                          </button>";
                }
            } else {
                echo "<p class='text-center text-muted'>Ödenmedi sipariş bulunmamaktadır.</p>";
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

        $.post("odeme_process.php", { action: "get_orders", table_number: tableNumber }, function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                var html = "<h3 class='text-danger'>" + tableNumber + " Siparişleri</h3>";
                html += "<table class='table table-bordered mt-3'>";
                html += "<thead class='table-dark'><tr><th>Ürün</th><th>Adet</th><th>Fiyat</th></tr></thead><tbody>";

                var totalPrice = 0;
                data.orders.forEach(function(order) {
                    html += "<tr>";
                    html += "<td>" + order.Name + "</td>";
                    html += "<td>" + order.Quantity + "</td>";
                    html += "<td>" + order.Price + " TL</td>";
                    html += "</tr>";
                    totalPrice += order.Price * order.Quantity;
                });

                html += "</tbody></table>";
                html += "<h4 class='text-danger'>Toplam: " + totalPrice + " TL</h4>";
                html += "<button id='complete-order' class='btn btn-success mt-3' data-order-id='" + data.orders[0].Order_ID + "'>Siparişi Ödendi Olarak İşaretle</button>";
                $("#order-details").html(html);
            } else {
                $("#order-details").html("<p class='text-muted'>" + data.message + "</p>");
            }
        });
    });

    $(document).on("click", "#complete-order", function() {
        var orderId = $(this).data("order-id");

        $.post("odeme_process.php", { action: "complete_order", order_id: orderId }, function(response) {
            var data = JSON.parse(response);
            alert(data.message);
            location.reload();
        });
    });
});
</script>

</body>
</html>
