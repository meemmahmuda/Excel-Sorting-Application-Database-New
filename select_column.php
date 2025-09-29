<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';
use PhpOffice\PhpSpreadsheet\IOFactory;


    $stmt = $pdo->query("SELECT * FROM files WHERE type='uploaded' ORDER BY created_at DESC LIMIT 2");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($files)<2) die("Please upload two files first.");

    $columns = [];
    foreach($files as $index=>$file){
        file_put_contents($file['filename'],$file['file_data']);
        $spreadsheet = IOFactory::load($file['filename']);
        $sheet = $spreadsheet->getActiveSheet();
        $columns[$index] = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0];
        unlink($file['filename']);
    }
 
?>

<form action="compare.php" method="post">
    <label>Select column from File 1 (<?php echo $files[1]['filename']; ?>):</label>
    <select name="column1" required>
        <?php foreach($columns[1] as $col): ?>
            <option value="<?php echo $col; ?>"><?php echo $col; ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Select column from File 2 (<?php echo $files[0]['filename']; ?>):</label>
    <select name="column2" required>
        <?php foreach($columns[0] as $col): ?>
            <option value="<?php echo $col; ?>"><?php echo $col; ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Compare & Download</button>
</form>
