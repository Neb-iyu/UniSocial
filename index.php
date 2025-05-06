<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to the home page if logged in
    header('Location: pages/home.php');
    exit();
}

// If not logged in, show the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginUser($email, $password)) {
        header('Location: pages/home.php');
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniSocial - Login</title>
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <div class="login-container">
        <h1>Welcome to UniSocial</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="pages/register.php">Register here</a></p>
    </div>
</body>
</html>
