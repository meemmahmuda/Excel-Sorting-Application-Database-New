<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$logFile = __DIR__ . '/error_log.txt';
function logError($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL, FILE_APPEND);
}

try {

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

    if (count($files) < 2) {
        throw new Exception("Please upload two files first.");
    }

    $dataFiles = [];
    $headers = [];
    $bankNames = [];

    foreach ($files as $index => $file) {

        $bankNames[$index] = $file['bank_name'];

        $tempFile = tempnam(sys_get_temp_dir(), 'xls');
        file_put_contents($tempFile, $file['file_data']);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();   

        unlink($tempFile);

        $headers[$index] = array_shift($rows);
        $dataFiles[$index] = $rows;
    }

   
    $colIndices = [];
    $colIndices[0] = array_search($col2, $headers[0]); 
    $colIndices[1] = array_search($col1, $headers[1]); 

    if ($colIndices[0] === false || $colIndices[1] === false) {
        throw new Exception("Selected column not found in one of the files.");
    }

    
    $lookup = array_column($dataFiles[1], $colIndices[1]);
    $lookup = array_flip(array_filter($lookup)); 

    $unmatched1 = [];
    foreach ($dataFiles[0] as $row) {
        $val = $row[$colIndices[0]] ?? null;
        if (!isset($lookup[$val])) {
            $unmatched1[] = $row;
        }
    }


    $lookup2 = array_flip(array_column($dataFiles[0], $colIndices[0]));
    $unmatched2 = [];
    foreach ($dataFiles[1] as $row) {
        $val = $row[$colIndices[1]] ?? null;
        if (!isset($lookup2[$val])) {
            $unmatched2[] = $row;
        }
    }


    function writeUnmatchedFile($pdo, $userId, $bankName, $header, $data) {
        if (empty($data)) return;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, null, 'A1');
        $sheet->fromArray($data, null, 'A2');

        $filename = "unmatched_in_".$bankName."_".date('Ymd_His').".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xls');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $fileContent = file_get_contents($tempFile);
        unlink($tempFile);

        $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_data, type, bank_name) VALUES (?, ?, ?, 'unmatched', ?)");
        $stmt->execute([$userId, $filename, $fileContent, $bankName]);
    }

    writeUnmatchedFile($pdo, $userId, $bankNames[0], $headers[0], $unmatched1);
    writeUnmatchedFile($pdo, $userId, $bankNames[1], $headers[1], $unmatched2);

    header("Location: download.php");
    exit;

} catch (Exception $e) {

    logError("COMPARE ERROR: " . $e->getMessage());

    echo "<p style='text-align:center; color:red; font-size:18px;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
