<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php'; 


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!isset($_GET['result'])) {
        header("Location: upload.php");
        exit;
    }
}

if (isset($_GET['result'])) {
    $result = $_GET['result'];
    $message = '';
    $color = '';
    $showDownload = false;

    if ($result === 'unmatched') {
        $message = "Unmatched data found!";
        $color = '#d9534f'; 
        $showDownload = true;
    } elseif ($result === 'matched') {
        $message = "No unmatched data found.";
        $color = '#28a745'; 
    }

    include 'header.php';
    
    echo '
    <div style="width:450px; margin:50px auto; padding:25px; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial, sans-serif; text-align:center;">
        <h2 style="color:#333; margin-bottom:20px;">Compare Result</h2>
        <p style="font-size:16px; color:'.$color.'; font-weight:bold; margin-bottom:25px;">'.htmlspecialchars($message).'</p>';
    
    if ($showDownload) {
        echo '<a href="download.php" style="display:inline-block; padding:12px 25px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px; font-weight:bold; transition:background 0.3s;">
                Go to Download Page
              </a>';
    }

    echo '</div>';
    exit;
}

$col1 = $_POST['column1'];
$col2 = $_POST['column2'];
$userId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT * FROM files WHERE type='uploaded' AND user_id=? ORDER BY created_at DESC LIMIT 2");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($files) < 2) {
    die("Please upload two files first.");
}

$dataFiles = [];
$headers = [];

foreach ($files as $index => $file) {
    file_put_contents($file['filename'], $file['file_data']);
    $spreadsheet = IOFactory::load($file['filename']);
    $sheet = $spreadsheet->getActiveSheet();
    $dataFiles[$index] = $sheet->toArray();
    $headers[$index] = array_shift($dataFiles[$index]); 
    unlink($file['filename']); 
}

$colIndex1 = array_search($col1, $headers[1]);
$colIndex2 = array_search($col2, $headers[0]);

if ($colIndex1 === false || $colIndex2 === false) {
    die("Column not found.");
}

$file1Col = array_column($dataFiles[1], $colIndex1);
$file2Col = array_column($dataFiles[0], $colIndex2);

$unmatched1 = array_filter($dataFiles[1], fn($row) => !in_array($row[$colIndex1], $file2Col));
$unmatched2 = array_filter($dataFiles[0], fn($row) => !in_array($row[$colIndex2], $file1Col));

if (empty($unmatched1) && empty($unmatched2)) {
    header("Location: compare.php?result=matched");
    exit;
} else {
    $newSpreadsheet = new Spreadsheet();

    $sheet1 = $newSpreadsheet->getActiveSheet();
    $sheet1->setTitle('Unmatched_File1');
    $sheet1->fromArray($headers[1], null, 'A1');
    $sheet1->fromArray(array_values($unmatched1), null, 'A2');

    $sheet2 = $newSpreadsheet->createSheet();
    $sheet2->setTitle('Unmatched_File2');
    $sheet2->fromArray($headers[0], null, 'A1');
    $sheet2->fromArray(array_values($unmatched2), null, 'A2');

    $tempFile = tempnam(sys_get_temp_dir(), 'xls');
    $writer = new Xlsx($newSpreadsheet);
    $writer->save($tempFile);
    $fileContent = file_get_contents($tempFile);
    unlink($tempFile);

    $timestamp = date('Ymd_His');
    $filename = "unmatched_{$timestamp}.xlsx";

    $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_data, type, created_at) VALUES (?, ?, ?, 'unmatched', NOW())");
    $stmt->execute([$userId, $filename, $fileContent]);

    header("Location: compare.php?result=unmatched");
    exit;
}
?>
