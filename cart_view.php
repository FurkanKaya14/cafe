<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<table border='1'>";
    echo "<tr><th>Ürün Adı</th><th>Fiyat</th><th>Miktar</th><th>Toplam</th><th>İşlem</th></tr>";

    $total = 0;
    foreach ($_SESSION['cart'] as $productId => $product) {
        $subtotal = $product['price'] * $product['quantity'];
        $total += $subtotal;
        echo "<tr>
                <td>{$product['name']}</td>
                <td>{$product['price']}₺</td>
                <td>{$product['quantity']}</td>
                <td>{$subtotal}₺</td>
                <td><button class='remove-item' data-id='{$productId}'>❌ Sepetten Çıkar</button></td>
              </tr>";
    }

    echo "<tr><td colspan='3'>Genel Toplam</td><td>{$total}₺</td><td></td></tr>";
    echo "</table>";
    echo "<p><button id='clear-cart' style='color: red;'>🗑️ Sepeti Temizle</button></p>";
} else {
    echo "<p>Sepetiniz boş.</p>";
}
?>
