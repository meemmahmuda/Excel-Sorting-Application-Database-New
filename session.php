<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {

    $stmt = $pdo->prepare("SELECT id, is_approved FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$user || $user['is_approved'] == 0) {
        session_unset();
        session_destroy();
        header("Location: login.php"); 
        exit;
    }

} else {
    header("Location: login.php");
    exit;
}
?>
