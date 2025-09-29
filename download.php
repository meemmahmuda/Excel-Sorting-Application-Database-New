<?php
require 'vendor/autoload.php';
include 'db.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1️⃣ Single file download (GET)
if(isset($_GET['id']) && !isset($_GET['delete'])){
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id=?");
    $stmt->execute([$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$file) die("File not found.");
    if(ob_get_length()) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'. $file['filename'] .'"');
    header('Cache-Control: max-age=0');
    echo $file['file_data'];
    exit;
}

// 2️⃣ Delete file (GET)
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM files WHERE id=?");
    $stmt->execute([$id]);
    header("Location: download.php");
    exit;
}

// 3️⃣ Bulk download (POST)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files'])){
    $ids = $_POST['files'];
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id IN (".implode(',', array_map('intval', $ids)).")");
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(empty($files)) die("No files selected.");

    $zip = new ZipArchive();
    $zipFile = tempnam(sys_get_temp_dir(), 'excel');
    $zip->open($zipFile, ZipArchive::CREATE);

    $filenameCount = [];
    foreach($files as $file){
        $tmpFile = tempnam(sys_get_temp_dir(),'xls');
        file_put_contents($tmpFile,$file['file_data']);

        // Ensure unique filename inside ZIP
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
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="selected_files.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

// 4️⃣ Fetch all files for table
$stmt = $pdo->query("SELECT * FROM files ORDER BY created_at DESC");
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>All Files</h2>

<form method="post">
<table border="1" cellpadding="5" cellspacing="0" id="fileTable">
<tr>
    <th><input type="checkbox" id="selectAll"></th>
    <th>Filename</th>
    <th>Type</th>
    <th>Time</th>
    <th>Action</th>
</tr>

<?php foreach($files as $index => $file): ?>
<tr class="fileRow" <?php if($index>=5) echo 'style="display:none;"'; ?>>
    <td><input type="checkbox" name="files[]" value="<?php echo $file['id']; ?>"></td>
    <td><?php echo htmlspecialchars($file['filename']); ?></td>
    <td><?php echo $file['type']; ?></td>
    <td><?php echo $file['created_at']; ?></td>
    <td>
        <a href="download.php?id=<?php echo $file['id']; ?>">Download</a> | 
        <a href="download.php?delete=<?php echo $file['id']; ?>" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<br>
<button type="submit">Download Selected</button>
<button type="button" id="seeMore">See More</button>
</form>

<script>
// Select / deselect all checkboxes
document.getElementById('selectAll').addEventListener('change', function(){
    let checkboxes = document.querySelectorAll('input[name="files[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// See More functionality
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
