<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$userId = $_SESSION['user_id']; 


$stmt = $pdo->prepare("SELECT * FROM files WHERE type='uploaded' AND user_id=? ORDER BY created_at DESC LIMIT 2");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($files) < 2) die("Please upload two files first.");

$columns = [];
foreach($files as $index => $file){
    file_put_contents($file['filename'], $file['file_data']);
    $spreadsheet = IOFactory::load($file['filename']);
    $sheet = $spreadsheet->getActiveSheet();
    $columns[$index] = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0];
    unlink($file['filename']);
}
?>

<div style="width:450px; margin:50px auto; padding:25px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Compare Files</h2>

    <form action="compare.php" method="post" style="display:flex; flex-direction:column; gap:15px;">
        <label for="column1">Select column from File 1 (<?php echo htmlspecialchars($files[1]['filename']); ?>):</label>
        <select name="column1" id="column1" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">
            <?php foreach($columns[1] as $col): ?>
                <option value="<?php echo htmlspecialchars($col); ?>"><?php echo htmlspecialchars($col); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="column2">Select column from File 2 (<?php echo htmlspecialchars($files[0]['filename']); ?>):</label>
        <select name="column2" id="column2" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">
            <?php foreach($columns[0] as $col): ?>
                <option value="<?php echo htmlspecialchars($col); ?>"><?php echo htmlspecialchars($col); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" style="padding:10px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">Compare & Download</button>
    </form>
</div>
