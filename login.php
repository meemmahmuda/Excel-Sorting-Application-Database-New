<?php
session_start();
require 'db.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password,$user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: upload.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<div style="width:300px; margin:50px auto; padding:20px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Login</h2>

    <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

    <form method="post" style="display:flex; flex-direction:column; gap:15px;">
        <label>Username:</label>
        <input type="text" name="username" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <label>Password:</label>
        <input type="password" name="password" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <button type="submit" style="padding:10px; background:#007BFF; color:white; border:none; border-radius:4px; cursor:pointer;">Login</button>
    </form>

    <p style="text-align:center; margin-top:15px;">
        <a href="register.php" style="color:#007BFF; text-decoration:none;">Register</a>
    </p>
</div>

