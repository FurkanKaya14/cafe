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
    <link rel="icon" type="image/png" href="party.png" sizes="196x196" />
    <link rel="stylesheet" type="text/css" href="odeme.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="text-center text-danger mb-4">Ödeme Bekleyen Masalar</h1>
    <div id="orders" class="d-flex flex-wrap justify-content-center gap-3">
        <?php
        try {
            $stmt = $pdo->query("SELECT DISTINCT c.Customer_ID, c.Name FROM Orders o JOIN Customer c ON o.Customer_ID = c.Customer_ID WHERE o.Status = 'completed' AND o.odeme = 'odenmedi'");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                $masalar = [];
                foreach ($orders as $order) {
                    $masalar[$order['Name']] = true;
                }
                foreach ($masalar as $masa => $value) {
                    echo "<button class='btn btn-outline-danger order-btn' data-table-number='{$masa}'>" . htmlspecialchars($masa) . "</button>";
                }
            } else {
                echo "<p class='text-center text-muted'>Ödenmeyen sipariş bulunmamaktadır.</p>";
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
                html += "<thead class='table-dark'><tr><th>Ürün</th><th>Adet</th><th>Fiyat</th><th>Ödenecek Adet</th></tr></thead><tbody>";

                data.orders.forEach(function(order) {
                    html += "<tr>";
                    html += "<td>" + order.Name + "</td>";
                    html += "<td data-initial-quantity='" + order.Quantity + "'>" + order.Quantity + "</td>";
                    html += "<td>" + order.Price + " TL</td>";
                    html += "<td><input type='number' class='pay-quantity' data-order-id='" + order.Order_ID + "' data-item-id='" + order.Item_ID + "' max='" + order.Quantity + "' value='" + order.Quantity + "'></td>";
                    html += "</tr>";
                });

                html += "</tbody></table>";
                html += "<h4 class='text-danger'>Ödenecek Toplam: <span id='pay-total'>0</span></h4>";
                html += "<button id='complete-order' class='btn btn-success mt-3'>Ödeme Al</button>";
                html += "<button id='close-bill' class='btn btn-danger mt-3 ms-2'>Hesabı Kapat</button>";
                $("#order-details").html(html);
                calculatePayTotal(); // İlk toplam hesaplaması
            } else {
                $("#order-details").html("<p class='text-muted'>" + data.message + "</p>");
            }
        });
    });

    $(document).on("change", ".pay-quantity", function() {
        calculatePayTotal(); // Miktar değiştiğinde toplamı güncelle
    });

    function calculatePayTotal() {
        var total = 0;
        $(".pay-quantity").each(function() {
            var quantity = Math.max(0, Number($(this).val())) || 0;
            var price = Number($(this).closest("tr").find("td:nth-child(3)").text().replace(" TL", "").trim()) || 0;
            total += quantity * price;
        });
        $("#pay-total").text(total.toFixed(2) + " TL"); // Toplam miktarı güncelle
    }

    $(document).on("click", "#complete-order", function() {
        var orderId = $(".pay-quantity").first().data("order-id");
        var items = [];

        $(".pay-quantity").each(function() {
            var quantityText = $(this).closest("tr").find("td:nth-child(2)").text().trim();
            var quantity = parseInt(quantityText, 10);
            var payQuantity = parseInt($(this).val(), 10) || 0;

            if (isNaN(quantity) || isNaN(payQuantity)) {
                console.error("Geçersiz sayı:", quantityText, payQuantity);
                return;
            }

            var newQuantity = Math.max(0, quantity - payQuantity);
            $(this).closest("tr").find("td:nth-child(2)").text(newQuantity.toString());

            items.push({
                item_id: $(this).data("item-id"),
                pay_quantity: payQuantity
            });
        });

        $.post("odeme_process.php", { action: "complete_order", order_id: orderId, items: JSON.stringify(items) }, function(response) {
            var data = JSON.parse(response);
            alert(data.message);
            location.reload();
        });
    });

    // Hesabı Kapat Butonu
    $(document).on("click", "#close-bill", function() {
        var orderId = $(".pay-quantity").first().data("order-id");

        $.post("odeme_process.php", { action: "close_bill", order_id: orderId }, function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert(data.message);
                $(".order-btn[data-table-number='" + data.table_number + "']").remove();
                $("#order-details").html("<p class='text-success'>Hesap başarıyla kapatıldı.</p>");
            } else {
                alert(data.message);
            }
        });
    });
});

</script>

</body>
</html>
