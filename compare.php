<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if($_SERVER['REQUEST_METHOD']!=='POST') die("Invalid request.");

$col1 = $_POST['column1'];
$col2 = $_POST['column2'];


    // Fetch last two uploaded files
    $stmt = $pdo->query("SELECT * FROM files WHERE type='uploaded' ORDER BY created_at DESC LIMIT 2");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($files)<2) die("Please upload two files first.");

    $dataFiles = [];
    $headers = [];

    foreach($files as $index=>$file){
        file_put_contents($file['filename'],$file['file_data']);
        $spreadsheet = IOFactory::load($file['filename']);
        $sheet = $spreadsheet->getActiveSheet();
        $dataFiles[$index] = $sheet->toArray();
        $headers[$index] = array_shift($dataFiles[$index]); // remove header
        unlink($file['filename']);
    }

    $colIndex1 = array_search($col1,$headers[1]);
    $colIndex2 = array_search($col2,$headers[0]);
    if($colIndex1===false||$colIndex2===false) die("Column not found.");

    $file1Col = array_column($dataFiles[1],$colIndex1);
    $file2Col = array_column($dataFiles[0],$colIndex2);

    $unmatched1 = array_filter($dataFiles[1], fn($row)=>!in_array($row[$colIndex1],$file2Col));
    $unmatched2 = array_filter($dataFiles[0], fn($row)=>!in_array($row[$colIndex2],$file1Col));

    if(empty($unmatched1) && empty($unmatched2)){
        $message = "No unmatched data found.";
        $hasUnmatched = false;
    } else {
        $message = "Unmatched data found!";
        $hasUnmatched = true;

        // Create Excel with unmatched data
        $newSpreadsheet = new Spreadsheet();

        $sheet1 = $newSpreadsheet->getActiveSheet();
        $sheet1->setTitle('Unmatched_File1');
        $sheet1->fromArray($headers[1],null,'A1');
        $sheet1->fromArray(array_values($unmatched1),null,'A2');

        $sheet2 = $newSpreadsheet->createSheet();
        $sheet2->setTitle('Unmatched_File2');
        $sheet2->fromArray($headers[0],null,'A1');
        $sheet2->fromArray(array_values($unmatched2),null,'A2');

        $tempFile = tempnam(sys_get_temp_dir(),'xls');
        $writer = new Xlsx($newSpreadsheet);
        $writer->save($tempFile);

        $fileContent = file_get_contents($tempFile);
        unlink($tempFile);

        // Store in database as 'unmatched'
        $stmt = $pdo->prepare("INSERT INTO files (filename,file_data,type,created_at) VALUES (?,?, 'unmatched', NOW())");
        $stmt->execute(['unmatched_rows.xlsx',$fileContent]);
    }


?>

<h2>Compare Result</h2>
<p><?php echo $message; ?></p>

<?php if($hasUnmatched): ?>
    <a href="download.php" style="padding:10px 20px; background:#4CAF50; color:white; text-decoration:none;">Go to Download Page</a>
<?php endif; ?>
