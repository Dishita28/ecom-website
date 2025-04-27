<?php
session_start();
$conn_users = new mysqli("localhost", "root", "", "users");
$conn_catalog = new mysqli("localhost", "root", "", "catalog");

// Handle Registration
if (isset($_POST['register'])) {
    $username = $_POST['reg_username'];
    $password = $_POST['reg_password'];
    $stmt = $conn_users->prepare("INSERT INTO Login (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        $reg_msg = "Registration successful. Please login.";
    } else {
        $reg_msg = "Error: " . $stmt->error;
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $username = $_POST['log_username'];
    $password = $_POST['log_password'];
    $stmt = $conn_users->prepare("SELECT password FROM Login WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    if ($stmt->fetch()) {
        if ($password === $stored_password) {
            $_SESSION['user'] = $username;
            $login_msg = "Login successful.";
        } else {
            $login_msg = "Invalid credentials.";
        }
    } else {
        $login_msg = "No such user.";
    }
}

// Handle Cart
if (isset($_POST['add'])) $_SESSION['cart'][] = ['n' => $_POST['n'], 'p' => $_POST['p']];
if (isset($_POST['clear'])) unset($_SESSION['cart']);
if (isset($_POST['remove'])) unset($_SESSION['cart'][$_POST['id']]);
if (isset($_POST['placeorder'])) {
    header("Location: wallet.php");
    exit();
}

// Handle Search
if (isset($_GET['q']) && $_GET['q'] !== '') {
    $q = $conn_catalog->real_escape_string($_GET['q']);
    $res = $conn_catalog->query("SELECT name, description, price, image FROM catalog WHERE name LIKE '%$q%' OR description LIKE '%$q%'");
} else {
    $res = $conn_catalog->query("SELECT name, description, price, image FROM catalog");
}
?>
<!DOCTYPE html>
<html>
<head><title>Shop App</title></head>
<body>

<?php if (!isset($_SESSION['user'])): ?>
<!-- Registration Form -->
<h2>Register</h2>
<form method="post">
    Email: <input type="email" name="reg_username" required><br><br>
    Password: <input type="password" name="reg_password" required><br><br>
    <input type="submit" name="register" value="Register">
</form>
<?php if (isset($reg_msg)) echo "<p>$reg_msg</p>"; ?>

<hr>

<!-- Login Form -->
<h2>Login</h2>
<form method="post">
    Email: <input type="email" name="log_username" required><br><br>
    Password: <input type="password" name="log_password" required><br><br>
    <input type="submit" name="login" value="Login">
</form>
<?php if (isset($login_msg)) echo "<p>$login_msg</p>"; ?>

<?php else: ?>
<!-- User is logged in -->
<h2>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</h2>
<form method="post">
    <button name="logout" value="1">Logout</button>
</form>

<!-- Cart Display -->
<h3>Your Cart</h3>
<?php if (!empty($_SESSION['cart'])): 
    $t = 0;
    foreach ($_SESSION['cart'] as $i => $c): ?>
        <?= htmlspecialchars($c['n']) ?> - ₹<?= htmlspecialchars($c['p']) ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $i ?>">
            <button name="remove">Remove</button>
        </form><br>
    <?php $t += $c['p']; endforeach; ?>
    <b>Total: ₹<?= $t ?></b><br>
    <form method="post">
        <button name="clear">Clear Cart</button>
        <button name="placeorder">Place Order</button>
    </form>
<?php else: ?>
    <p>Cart is empty.</p>
<?php endif; ?>
<hr>

<!-- Catalog with Search -->
<h2>Catalogue</h2>
<form method="get">
    <input type="text" name="q" placeholder="Search products..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
    <input type="submit" value="Search">
</form>

<?php
if ($res && $res->num_rows > 0) {
    while($r = $res->fetch_assoc()): ?>
    <div style="border:1px solid #aaa; padding:10px; margin:10px; width:200px; display:inline-block;">
        <img src="<?= htmlspecialchars($r['image']) ?>" style="width:100%;height:200px;"><br>
        <b><?= htmlspecialchars($r['name']) ?></b><br>
        <?= htmlspecialchars($r['description']) ?><br>
        ₹<?= htmlspecialchars($r['price']) ?><br>
        <form method="post">
            <input type="hidden" name="n" value="<?= htmlspecialchars($r['name']) ?>">
            <input type="hidden" name="p" value="<?= $r['price'] ?>">
            <button name="add">Add to Cart</button>
        </form>
    </div>
<?php endwhile;
} else {
    echo "No products found.";
}
?>

<?php endif; ?>

<?php
// Handle Logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

</body>
</html>
