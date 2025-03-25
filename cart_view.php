<html>
<body>
    
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
                <td>
                    <button class='decrease-item' data-id='{$productId}'>
                    <img src='eksi.png' alt='eksi' style='width: 20px; height: 20px;'>
                    </button>
                    <span>{$product['quantity']}</span>
                    <button class='increase-item' data-id='{$productId}'>
                    <img src='arti.png' alt='arti' style='width: 20px; height: 20px;'>
                    </button>
                </td>
                <td>{$subtotal}₺</td>
                <td><button class='remove-item' data-id='{$productId}'>
                <img src='sil.png' alt='sil' style='width: 20px; height: 20px;'>
                </button></td>
              </tr>";
    }

    echo "<tr><td colspan='3'>Genel Toplam</td><td>{$total}₺</td><td></td></tr>";
    echo "</table>";
} else {
    echo "<p class='bos_sepet'>Sepetiniz boş.</p>";
}
?>
</body>
</html>
