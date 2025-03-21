<html>
<head>
    <link rel="stylesheet" type="text/css" href="cart.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
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
    die("<p style='color: red;'>Veritabanı Hatası: " . $e->getMessage() . "</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === "add" && isset($_POST['id'], $_POST['name'], $_POST['price'])) {
        $productId = $_POST['id'];
        $productName = $_POST['name'];
        $productPrice = (float)$_POST['price'];

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$productId] = [
                'name' => $productName,
                'price' => $productPrice,
                'quantity' => 1
            ];
        }
    }

    if ($action === "remove" && isset($_POST['id'])) {
        $productId = $_POST['id'];
        unset($_SESSION['cart'][$productId]);
    }

    if ($action === "clear") {
        unset($_SESSION['cart']);
    }

    // Miktarı artırma
    if ($action === "increase" && isset($_POST['id'])) {
        $productId = $_POST['id'];
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += 1;
        }
    }

    // Miktarı azaltma
    if ($action === "decrease" && isset($_POST['id'])) {
        $productId = $_POST['id'];
        if (isset($_SESSION['cart'][$productId])) {
            if ($_SESSION['cart'][$productId]['quantity'] > 1) {
                $_SESSION['cart'][$productId]['quantity'] -= 1;
            } else {
                unset($_SESSION['cart'][$productId]); // Eğer 1 ise, ürünü tamamen sil
            }
        }
    }

    exit;
}
?>

<h1 class="text-center text-danger mt-3">Menü</h1>
<div class="container">
    <div class="row justify-content-center g-4">
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM Menu_Item");
            $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($menuItems) {
                foreach ($menuItems as $item) {
                    // Dosya adını menü ismine göre oluştur (boşlukları alt çizgiyle değiştir, küçük harfe çevir)
                    $sanitizedFileName = strtolower(str_replace(" ", "_", $item['Name'])) . ".jpg";
                    $imagePath = file_exists("images/$sanitizedFileName") ? "images/$sanitizedFileName" : "images/default.jpg";

                    echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center'>
                            <div class='card shadow-sm h-100' style='width: 18rem;'>
                                <img src='{$imagePath}' class='card-img-top' alt='{$item['Name']}' style='height: 200px; object-fit: cover;'>
                                <div class='card-body text-center d-flex flex-column'>
                                    <h5 class='card-title text-dark'>{$item['Name']}</h5>
                                    <p class='text-danger fw-bold'>{$item['Price']}₺</p>
                                    <button class='btn btn-danger add-to-cart' 
                                        data-id='{$item['Item_ID']}' 
                                        data-name='{$item['Name']}' 
                                        data-price='{$item['Price']}'>
                                        Sepete Ekle
                                    </button>
                                </div>
                            </div>
                          </div>";
                }
            } else {
                echo "<p class='text-center'>Menüde ürün bulunmamaktadır.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</div>

<h1 class="text-center text-danger mt-4">Sepetiniz</h1>
<div id="cart" class="container">
    <?php include "cart_view.php"; ?>
</div>

<div class="container mt-4">
    <form id="order-form">
        <label for="table_number" class="form-label">Masa Numarası:</label>
        <input type="text" id="table_number" name="table_number" class="form-control mb-2" required>
        <button type="submit" class="btn btn-danger">Siparişi Oluştur</button>
    </form>
</div>
<div id="order-result" class="container mt-3"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Ürün ekleme
    $(".add-to-cart").click(function() {
        var productId = $(this).data("id");
        var productName = $(this).data("name");
        var productPrice = $(this).data("price");

        $.post("cart.php", { action: "add", id: productId, name: productName, price: productPrice }, function() {
            $("#cart").load("cart_view.php");
        });
    });

    // Ürün çıkarma
    $(document).on("click", ".remove-item", function() {
        var productId = $(this).data("id");

        $.post("cart.php", { action: "remove", id: productId }, function() {
            $("#cart").load("cart_view.php");
        });
    });

    // Miktarı artırma
    $(document).on("click", ".increase-item", function() {
        var productId = $(this).data("id");

        $.post("cart.php", { action: "increase", id: productId }, function() {
            $("#cart").load("cart_view.php");
        });
    });

    // Miktarı azaltma
    $(document).on("click", ".decrease-item", function() {
        var productId = $(this).data("id");

        $.post("cart.php", { action: "decrease", id: productId }, function() {
            $("#cart").load("cart_view.php");
        });
    });

    // Sepeti temizleme
    $("#clear-cart").click(function() {
        $.post("cart.php", { action: "clear" }, function() {
            $("#cart").load("cart_view.php");
        });
    });

    // Sipariş oluşturma
    $("#order-form").submit(function(e) {
        e.preventDefault();
        var tableNumber = $("#table_number").val();

        $.post("order.php", { action: "order", table_number: tableNumber }, function(response) {
            alert(response);
            $("#cart").load("cart_view.php");
        });
    });
});
</script>

</body>
</html>