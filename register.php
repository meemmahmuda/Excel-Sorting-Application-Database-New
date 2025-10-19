<?php
/*
session_start();
require 'db.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if($username && $password){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username,password) VALUES (?,?)");
        try{
            $stmt->execute([$username, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header("Location: login.php");
            exit;
        } catch(PDOException $e){
            $error = "Username already exists.";
        }
    } else {
        $error = "All fields are required.";
    }
}
*/
?>

<?php
/*
<div style="width:300px; margin:50px auto; padding:20px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Register</h2>

    <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

    <form method="post" style="display:flex; flex-direction:column; gap:15px;">
        <label>Username:</label>
        <input type="text" name="username" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <label>Password:</label>
        <input type="password" name="password" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <button type="submit" style="padding:10px; background:#28A745; color:white; border:none; border-radius:4px; cursor:pointer;">Register</button>
    </form>

    <p style="text-align:center; margin-top:15px;">
        <a href="login.php" style="color:#007BFF; text-decoration:none;">Login</a>
    </p>
</div>
*/
?>
