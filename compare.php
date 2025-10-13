<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: select_column.php");
    exit;
}

$col1 = $_POST['column1'] ?? '';
$col2 = $_POST['column2'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM files WHERE type='uploaded' AND user_id=? ORDER BY created_at DESC LIMIT 2");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($files) < 2) die("Please upload two files first.");

$dataFiles = [];
$headers = [];
$bankNames = [];

foreach ($files as $index => $file) {
    $bankNames[$index] = $file['bank_name'];

    $tempFile = tempnam(sys_get_temp_dir(), 'xls');
    file_put_contents($tempFile, $file['file_data']);

    $spreadsheet = IOFactory::load($tempFile);
    $sheet = $spreadsheet->getActiveSheet();
    $dataFiles[$index] = $sheet->toArray();

    $headers[$index] = array_shift($dataFiles[$index]); 
    unlink($tempFile);
}

$colIndices = [];
foreach ($dataFiles as $i => $data) {
    $colName = ($i === 0) ? $col2 : $col1; 
    $colIndices[$i] = array_search($colName, $headers[$i]);
    if ($colIndices[$i] === false) die("Column not found in file ".$bankNames[$i]);
}

$fileCols = [];
foreach ($dataFiles as $i => $data) {
    $fileCols[$i] = array_column($data, $colIndices[$i]);
}

$unmatched = [];
foreach ($dataFiles as $i => $data) {
    $otherIndex = ($i === 0) ? 1 : 0;
    $unmatched[$i] = [];
    foreach ($data as $row) {
        if (!isset($row[$colIndices[$i]])) continue;
        if (!in_array($row[$colIndices[$i]], $fileCols[$otherIndex])) {
            $unmatched[$i][] = $row;
        }
    }
    $unmatched[$i] = array_values($unmatched[$i]); 
}

if (empty($unmatched[0]) && empty($unmatched[1])) {
    echo "<p style='text-align:center; color:green;'>No unmatched data found in both files.</p>";
    exit;
}

function writeUnmatchedFile($pdo, $userId, $bankName, $header, $data) {
    if (empty($data)) return;

    $rows = array_map(fn($row) => array_slice($row, 0, count($header)), $data);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($header, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $filename = "unmatched_in_".$bankName."_".date('Ymd_His').".xlsx";
    $tempFile = tempnam(sys_get_temp_dir(), 'xls');

    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    $fileContent = file_get_contents($tempFile);
    unlink($tempFile);

    $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_data, type, bank_name, created_at) VALUES (?, ?, ?, 'unmatched', ?, NOW())");
    $stmt->execute([$userId, $filename, $fileContent, $bankName]);
}

foreach ($files as $i => $file) {
    writeUnmatchedFile($pdo, $userId, $bankNames[$i], $headers[$i], $unmatched[$i]);
}

header("Location: download.php");
exit;
