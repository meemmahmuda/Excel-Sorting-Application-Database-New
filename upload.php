<?php
include 'db.php';
include 'session.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $userId = $_SESSION['user_id']; 

    for($i=1; $i<=2; $i++){
        if(isset($_FILES["file$i"])){
            $filename = $_FILES["file$i"]["name"];
            $fileData = file_get_contents($_FILES["file$i"]["tmp_name"]);
            $bankName = $_POST["bank_name$i"]; 

            $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_data, type, bank_name) VALUES (?,?,?,?,?)");
            $stmt->execute([
                $userId, 
                $filename, 
                $fileData, 
                'uploaded', 
                $bankName
            ]);
        }
    }

    header("Location: select_column.php");
    exit;
}

include 'header.php';
?>

<div style="width:400px; margin:50px auto; padding:20px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Upload Files</h2>

    <form action="upload.php" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:15px;">

        <label>Upload File 1:</label>
        <input type="file" name="file1" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <label>Select Portal for File 1:</label>
        <select name="bank_name1" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="">-- Select Bank --</option>
            <option value="DNCC Bank Portal">DNCC Bank Portal</option>
            <option value="DBBL Holding">DBBL Holding</option>
            <option value="DBBL Holding Due">DBBL Holding Due</option>
            <option value="DBBL MFS">DBBL MFS</option>
            <option value="DBBL MFS Due">DBBL MFS Due</option>
            <option value="Bkash Holding">Bkash Holding</option>
            <option value="Sonali Bank">Sonali Bank</option>
            <option value="Standard Bank">Standard Bank</option>
            <option value="Modhumoti Bank">Modhumoti Bank</option>
            <option value="Trust TAP Holding">Trust TAP Holding</option>
            <option value="Trust TAP TL">Trust TAP TL</option>
            <option value="Upay MFS">Upay MFS</option>
            <option value="OK Wallet">OK Wallet</option>
            <option value="DBBL TL Collection">DBBL TL Collection</option>
            <option value="DBBL TL Correction">DBBL TL Correction</option>
            <option value="Bkash TL">Bkash TL</option>
        </select>

        <label>Upload File 2:</label>
        <input type="file" name="file2" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">

        <label>Select Portal for File 2:</label>
        <select name="bank_name2" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="">-- Select Bank --</option>
            <option value="DNCC Bank Portal">DNCC Bank Portal</option>
            <option value="DBBL Holding">DBBL Holding</option>
            <option value="DBBL Holding Due">DBBL Holding Due</option>
            <option value="DBBL MFS">DBBL MFS</option>
            <option value="DBBL MFS Due">DBBL MFS Due</option>
            <option value="Bkash Holding">Bkash Holding</option>
            <option value="Sonali Bank">Sonali Bank</option>
            <option value="Standard Bank">Standard Bank</option>
            <option value="Modhumoti Bank">Modhumoti Bank</option>
            <option value="Trust TAP Holding">Trust TAP Holding</option>
            <option value="Trust TAP TL">Trust TAP TL</option>
            <option value="Upay MFS">Upay MFS</option>
            <option value="OK Wallet">OK Wallet</option>
            <option value="DBBL TL Collection">DBBL TL Collection</option>
            <option value="DBBL TL Correction">DBBL TL Correction</option>
            <option value="Bkash TL">Bkash TL</option>
        </select>

        <button type="submit" style="padding:10px; background:#007BFF; color:white; border:none; border-radius:4px; cursor:pointer;">Upload & Store</button>
    </form>
</div>
