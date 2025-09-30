<?php
include 'db.php';
include 'session.php';
include 'header.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $userId = $_SESSION['user_id']; 

    for($i=1; $i<=2; $i++){
        if(isset($_FILES["file$i"])){
            $filename = $_FILES["file$i"]["name"];
            $fileData = file_get_contents($_FILES["file$i"]["tmp_name"]);

            
            $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_data, type) VALUES (?,?,?, 'uploaded')");
            $stmt->execute([$userId, $filename, $fileData]);
        }
    }

    header("Location: select_column.php");
    exit;
}
?>

<div style="width:400px; margin:50px auto; padding:20px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Upload Files</h2>

    <form action="upload.php" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:15px;">
        <label>Upload File 1:</label>
        <input type="file" name="file1" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <label>Upload File 2:</label>
        <input type="file" name="file2" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <button type="submit" style="padding:10px; background:#007BFF; color:white; border:none; border-radius:4px; cursor:pointer;">Upload & Store</button>
    </form>
</div>

