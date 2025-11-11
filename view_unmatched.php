<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div style="text-align:center; margin-top:50px; font-family:Arial,sans-serif;">
            <h2 style="color:#d9534f;">Access Denied</h2>
            <p>You do not have permission to view this page.</p>
          </div>';
    exit;
}

$bankList = $pdo->query("SELECT DISTINCT bank_name FROM files WHERE type='unmatched'")->fetchAll(PDO::FETCH_COLUMN);
$userList = $pdo->query("SELECT id, username FROM users WHERE is_approved = 1")->fetchAll(PDO::FETCH_ASSOC);

$bankFilter = $_POST['bank_name'] ?? '';
$userFilter = $_POST['user_id'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
?>

<div style="width:90%; max-width:1200px; margin:30px auto; font-family:Arial,sans-serif;">
    <h2 style="text-align:center; color:#333; margin-bottom:20px;">Unmatched Files Overview</h2>

    <form method="post" style="text-align:center; margin-bottom:25px;">
        <label><b>Bank:</b></label>
        <select name="bank_name" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- All Banks --</option>
            <?php foreach ($bankList as $bank): ?>
                <option value="<?= htmlspecialchars($bank) ?>" <?= $bank === $bankFilter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($bank) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>User:</b></label>
        <select name="user_id" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- All Users --</option>
            <?php foreach ($userList as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>Date Range:</b></label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="padding:8px;">
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="padding:8px;">

        <button type="submit" style="padding:8px 15px; margin-left:10px; background:#28a745; color:white; border:none; border-radius:4px;">Show</button>
    </form>

<?php

$sql = "SELECT f.*, u.username 
        FROM files f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.type = 'unmatched'";
$params = [];

if ($bankFilter !== '') { $sql .= " AND f.bank_name = ?"; $params[] = $bankFilter; }
if ($userFilter !== '') { $sql .= " AND f.user_id = ?"; $params[] = $userFilter; }
if ($startDate !== '' && $endDate !== '') {
    $sql .= " AND DATE(f.created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fileRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalRows = 0;
foreach ($fileRecords as $file) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'xls_');
    file_put_contents($tmpFile, $file['file_data']);
    $spreadsheet = IOFactory::load($tmpFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rowCount = $sheet->getHighestDataRow();
    if ($rowCount > 1) $totalRows += ($rowCount - 1);
    unlink($tmpFile);
}

if ($bankFilter || $userFilter || ($startDate && $endDate)) {
    echo '<h3 style="color:#007bff; text-align:center; margin-bottom:20px;">
            Total Unmatched Data Rows (All Files): <b>' . number_format($totalRows) . '</b>
          </h3>';
}

if (empty($fileRecords)) {
    echo "<p style='text-align:center; color:#d9534f;'>No unmatched files found.</p>";
} else {
    foreach ($fileRecords as $file):
        $tmpFile = tempnam(sys_get_temp_dir(), 'xls_');
        file_put_contents($tmpFile, $file['file_data']);
        $spreadsheet = IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        $rowCount = $sheet->getHighestDataRow();
        $fileRowCount = ($rowCount > 1) ? ($rowCount - 1) : 0;


        $headers = array_map('strtolower', $data[0]);
        $amountCol = -1;
        foreach ($headers as $i => $h) {
            if (strpos($h, 'amount') !== false || strpos($h, 'amt') !== false) {
                $amountCol = $i;
                break;
            }
        }

        
        $totalAmount = 0;
        if ($amountCol >= 0) {
            for ($i = 1; $i < count($data); $i++) {
                $val = preg_replace('/[^0-9.\-]/', '', $data[$i][$amountCol]);
                if (is_numeric($val)) $totalAmount += $val;
            }
        }

        unlink($tmpFile);
?>
        <div style="margin-bottom:30px;">
            <h3>File: <?= htmlspecialchars($file['filename']) ?>
                (Bank: <?= htmlspecialchars($file['bank_name']) ?>, User: <?= htmlspecialchars($file['username']) ?>)
            </h3>

            <p><b>Unmatched Rows in This File:</b> <?= number_format($fileRowCount) ?></p>
            <p><b>Total TK Amount:</b> <?= number_format($totalAmount, 2) ?> </p>

            <div style="overflow-x:auto; margin-top:10px;">
                <table style="width:100%; border-collapse:collapse; min-width:900px;">
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td style="border:1px solid #ddd; padding:5px;"><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
<?php
    endforeach;
}
?>
</div>
