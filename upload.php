<?php
include 'db.php';
include 'session.php';
include 'header.php';
if($_SERVER['REQUEST_METHOD']==='POST'){

        for($i=1;$i<=2;$i++){
            if(isset($_FILES["file$i"])){
                $filename = $_FILES["file$i"]["name"];
                $fileData = file_get_contents($_FILES["file$i"]["tmp_name"]);

                $stmt = $pdo->prepare("INSERT INTO files (filename,file_data,type) VALUES (?,?, 'uploaded')");
                $stmt->execute([$filename,$fileData]);
            }
        }
        header("Location: select_column.php");
        exit;
    
}
?>


<form action="upload.php" method="post" enctype="multipart/form-data">
    <label>Upload File 1:</label>
    <input type="file" name="file1" required><br><br>
    <label>Upload File 2:</label>
    <input type="file" name="file2" required><br><br>
    <button type="submit">Upload & Store</button>
</form>

