<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$userId = $_SESSION['user_id']; 


if(isset($_GET['id']) && !isset($_GET['delete'])){
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$file) die("File not found.");
    if(ob_get_length()) ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'. $file['filename'] .'"');
    header('Cache-Control: max-age=0');
    echo $file['file_data'];
    exit;
}


if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM files WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    header("Location: download.php");
    exit;
}


if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files'])){
    $ids = $_POST['files'];

    $stmt = $pdo->prepare("SELECT * FROM files WHERE id IN (".implode(',', array_map('intval', $ids)).") AND user_id=?");
    $stmt->execute([$userId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(empty($files)) die("No files selected.");

    $zip = new ZipArchive();
    $zipFile = tempnam(sys_get_temp_dir(), 'excel');
    $zip->open($zipFile, ZipArchive::CREATE);

    $filenameCount = [];
    foreach($files as $file){
        $tmpFile = tempnam(sys_get_temp_dir(),'xls');
        file_put_contents($tmpFile,$file['file_data']);

        $name = $file['filename'];
        if(isset($filenameCount[$name])){
            $filenameCount[$name]++;
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = $base . '_' . $filenameCount[$file['filename']] . '.' . $ext;
        } else {
            $filenameCount[$name] = 0;
        }

        $zip->addFile($tmpFile,$name);
    }

    $zip->close();
    if(ob_get_length()) ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="selected_files.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<div style="width:90%; max-width:900px; margin:30px auto; font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333; margin-bottom:20px;">All Files</h2>

    <form method="post">
        <table id="fileTable" style="width:100%; border-collapse:collapse; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background:#4CAF50; color:white; text-align:left;">
                    <th style="padding:10px;"><input type="checkbox" id="selectAll"></th>
                    <th style="padding:10px;">Filename</th>
                    <th style="padding:10px;">Type</th>
                    <th style="padding:10px;">Time</th>
                    <th style="padding:10px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($files as $index => $file): ?>
                <tr class="fileRow" <?php if($index>=5) echo 'style="display:none;"'; ?> style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;"><input type="checkbox" name="files[]" value="<?php echo $file['id']; ?>"></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($file['filename']); ?></td>
                    <td style="padding:8px;"><?php echo $file['type']; ?></td>
                    <td style="padding:8px;"><?php echo $file['created_at']; ?></td>
                    <td style="padding:8px;">
                        <a href="download.php?id=<?php echo $file['id']; ?>" style="color:#4CAF50; text-decoration:none; margin-right:10px;">Download</a>
                        <a href="download.php?delete=<?php echo $file['id']; ?>" style="color:#d9534f; text-decoration:none;" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:20px; text-align:center;">
            <button type="submit" style="padding:10px 20px; background:#4CAF50; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-right:10px;">Download Selected</button>
            <button type="button" id="seeMore" style="padding:10px 20px; background:#2196F3; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">See More</button>
        </div>
    </form>
</div>

<script>

document.getElementById('selectAll').addEventListener('change', function(){
    let checkboxes = document.querySelectorAll('input[name="files[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});


let shownRows = 5;
document.getElementById('seeMore').addEventListener('click', function(){
    let rows = document.querySelectorAll('.fileRow');
    let count = 0;
    for(let i=shownRows; i<rows.length && count<5; i++, count++){
        rows[i].style.display = '';
    }
    shownRows += count;
    if(shownRows >= rows.length) this.style.display = 'none';
});
</script>


<script>
document.getElementById('selectAll').addEventListener('change', function(){
    let checkboxes = document.querySelectorAll('input[name="files[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

let shownRows = 5;
document.getElementById('seeMore').addEventListener('click', function(){
    let rows = document.querySelectorAll('.fileRow');
    let count = 0;
    for(let i=shownRows; i<rows.length && count<5; i++, count++){
        rows[i].style.display = '';
    }
    shownRows += count;
    if(shownRows >= rows.length) this.style.display = 'none';
});
</script>
