<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';

$userId = $_SESSION['user_id'];

if(!isset($_GET['id'])) die("Invalid request.");

$id = (int)$_GET['id'];


$stmt = $pdo->prepare("SELECT * FROM files WHERE id=? AND user_id=?");
$stmt->execute([$id, $userId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$file) die("File not found.");



if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(!isset($_FILES['excel']) || $_FILES['excel']['error'] != 0){
        die("Please upload a valid Excel file.");
    }

    $newFileData  = file_get_contents($_FILES['excel']['tmp_name']);
    $newFileName  = $_FILES['excel']['name'];

  
    $stmt = $pdo->prepare("
        UPDATE files 
        SET filename=?, file_data=?, created_at=NOW()
        WHERE id=? AND user_id=?
    ");

    $stmt->execute([
        $newFileName,
        $newFileData,
        $id,
        $userId
    ]);

    echo "<script>alert('File updated successfully!'); window.location='download.php';</script>";
    exit;
}

include 'header.php';
?>

<div style="width:90%; max-width:600px; margin:40px auto; font-family:Arial;">
    <h2>Edit Excel File</h2>

    <p><b>Current File:</b> <?php echo htmlspecialchars($file['filename']); ?></p>
    <p><b>Bank / Portal:</b> <?php echo htmlspecialchars($file['bank_name']); ?></p>
    <p><b>File Type:</b> <?php echo htmlspecialchars($file['type']); ?></p>

    <hr><br>

    <a href="download.php?id=<?php echo $file['id']; ?>" 
       style="padding:8px 15px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;">
        Download Current File
    </a>

    <form method="post" enctype="multipart/form-data" style="margin-top:25px;">
        <label><b>Upload Edited Excel File:</b></label><br><br>
        <input type="file" name="excel" required><br><br>

        <button type="submit" 
                style="padding:10px 18px; background:#2196F3; color:white; border:none; border-radius:5px;">
            Save Updated File
        </button>
    </form>
</div>
