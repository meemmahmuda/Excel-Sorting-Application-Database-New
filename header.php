<div style="background:#f1f1f1; padding:10px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">

    <div style="font-size:16px; color:#333; margin-right:20px;">
        <span>Welcome, <strong style="color:#007BFF;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
    </div>

    <div>
        <a href="upload.php" style="padding:6px 12px; background:#007BFF; color:white; text-decoration:none; border-radius:4px; margin-right:5px;">Upload</a>
        <a href="download.php" style="padding:6px 12px; background:#28A745; color:white; text-decoration:none; border-radius:4px; margin-right:5px;">Download</a>

        <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
            <a href="view_unmatched.php" style="padding:6px 12px; background:#ff00b7; color:white; text-decoration:none; border-radius:4px; margin-right:5px;">View Unmatched</a>
        <?php endif; ?>

        <a href="logout.php" style="padding:6px 12px; background:#DC3545; color:white; text-decoration:none; border-radius:4px;">Logout</a>
    </div>
</div>
