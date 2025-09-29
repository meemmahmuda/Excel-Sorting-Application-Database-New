<?php
// header.php

?>

<div style="background:#f1f1f1;padding:10px;margin-bottom:20px;">
    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
    <a href="logout.php" style="float:right;">Logout</a>
</div>
