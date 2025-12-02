<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div style="text-align:center; margin-top:50px; font-family:Arial,sans-serif;">
            <h2 style="color:#d9534f;">Access Denied</h2>
            <p>You do not have permission to view this page.</p>
          </div>';
    exit;
}

function parseExcelDate($value) {
    if (is_numeric($value)) {
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
    }

    $value = trim($value);
    if (empty($value)) return null;

    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $value, $m)) {
        $d = $m[1]; $mth = $m[2]; $y = $m[3];
        return sprintf('%04d-%02d-%02d', $y, $mth, $d);
    }

    $ts = strtotime($value);
    if ($ts !== false) return date('Y-m-d', $ts);

    return null;
}

$bankList = $pdo->query("SELECT DISTINCT bank_name FROM files WHERE type='unmatched'")->fetchAll(PDO::FETCH_COLUMN);
$userList = $pdo->query("SELECT id, username FROM users WHERE is_approved = 1 AND role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);

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

        <button type="submit" style="padding:8px 15px; margin-left:10px; background:#28a745; color:white; border:none; border-radius:4px;">
            Show
        </button>
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sql = "SELECT f.*, u.username
            FROM files f
            JOIN users u ON f.user_id = u.id
            WHERE f.type = 'unmatched'";
    $params = [];
    if ($bankFilter !== '') { $sql .= " AND f.bank_name = ?"; $params[] = $bankFilter; }
    if ($userFilter !== '') { $sql .= " AND f.user_id = ?"; $params[] = $userFilter; }

    $sql .= " ORDER BY f.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fileRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columnsToUse = [
        ['docnumber'=>'TL No','tranno'=>'Txn ID','cdate'=>'Date','totalamt'=>'Amount','gateway'=>'Gateway','PaymentType'=>'Payment Type','status'=>'Status'],
        ['e-holding'=>'E-Holding','transactio id'=>'Txn ID','date'=>'Date','paid amount'=>'Amount','gateway'=>'Gateway','status'=>'Status'],
        ['bill no'=>'Holding No / TL No','txn id'=>'Txn ID','territory code'=>'Date','amount'=>'Amount','status'=>'Status'],
        ['BILLER_REF_NO'=>'Holding No','TRANSACTION_ID'=>'Txn ID','TXN_DATE'=>'Date','TXN_AMT'=>'Amount','STATUS'=>'Status'],
        ['Account Number'=>'Holding No / TL No','bKash Transaction ID'=>'Txn ID','Pay Date'=>'Date','Total Amount'=>'Amount','status'=>'Status'],
        ['BENEFICIARYNAME'=>'Holding No','BANKTRANID'=>'Txn ID','TRANDATE'=>'Date','REQAMOUNT'=>'Amount','status'=>'Status'],
        ['E-Holding No'=>'Holding No','Transactio ID'=>'Txn ID','Payment Date'=>'Date','Paid Amount'=>'Amount','status'=>'Status'],
        ['Holding no'=>'Holding No','Payment no'=>'Txn ID','Date'=>'Date','Total amount'=>'Amount','status'=>'Status'],
        ['Transaction Id'=>'Txn ID','Transaction Date'=>'Date','Amount'=>'Amount','status'=>'Status'],
        ['E-Holding Number'=>'Holding No','Transaction ID (DNCC)'=>'Txn ID','Date & Time'=>'Date','Amount BDT'=>'Amount','status'=>'Status'],
        ['Holding No'=>'Holding No','TransactionNo'=>'Txn ID','Transaction Date'=>'Date','Amount'=>'Amount','status'=>'Status']
    ];

    if (!empty($fileRecords)) {
        $filteredFileData = [];
        $allTxnIds = [];

        foreach ($fileRecords as $file) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'xls_');
            file_put_contents($tmpFile, $file['file_data']);
            $spreadsheet = IOFactory::load($tmpFile);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
            unlink($tmpFile);

            $headers = array_map('strtolower', $data[0]);
            $dateCol = null;
            foreach ($headers as $idx => $h) {
                if (in_array(strtolower($h), ['cdate','date','txn_date','payment date','pay date','territory code','txndate','date & time'])) {
                    $dateCol = $idx;
                    break;
                }
            }

            $filteredData = [$data[0]];
            for ($i = 1; $i < count($data); $i++) {
                $cellDate = $dateCol === null ? null : $data[$i][$dateCol];
                $parsedDate = parseExcelDate($cellDate);
                if ($parsedDate !== null && $startDate && $endDate) {
                    if ($parsedDate >= $startDate && $parsedDate <= $endDate) $filteredData[] = $data[$i];
                } elseif (!$startDate && !$endDate) {
                    $filteredData[] = $data[$i];
                }
            }

            $fileRowCount = count($filteredData) - 1;
            if ($fileRowCount <= 0) continue;

            $txnCol = null;
            foreach ($headers as $idx => $h) {
                if (in_array($h, ['tranno','txn id','transactio id','transaction_id','transaction id','transactionno','bKash Transaction ID','banktranid','payment no'])) {
                    $txnCol = $idx;
                    break;
                }
            }

            if ($txnCol !== null) {
                for ($i = 1; $i < count($filteredData); $i++) {
                    $txn = trim($filteredData[$i][$txnCol]);
                    if ($txn !== '') $allTxnIds[$txn][] = true;
                }
            }

            $filteredFileData[] = ['file'=>$file, 'data'=>$filteredData, 'txnCol'=>$txnCol];
        }

        $duplicateHighlightIds = [];
        foreach ($allTxnIds as $txn => $arr) {
            if (count($arr) > 1) $duplicateHighlightIds[$txn] = 1;
        }

        $firstOccurrenceCount = 0;
        $seenTxnIds = [];

        echo '<h3 style="color:#007bff; text-align:center; margin-bottom:20px;">
                Total Unmatched Data (All Files): <b id="total-uncolored"></b>
              </h3>';

        foreach ($filteredFileData as $fData):
            $file = $fData['file'];
            $data = $fData['data'];
            $fileRowCount = count($data) - 1;
            $headers = array_map('strtolower', $data[0]);

            $selectedColumns = [];
            foreach ($columnsToUse as $colSet) {
                $allFound = true;
                foreach ($colSet as $key => $label) {
                    if (strtolower($key) === 'status') continue;
                    if (array_search(strtolower($key), $headers) === false) {
                        $allFound = false;
                        break;
                    }
                }
                if ($allFound) {
                    $selectedColumns = $colSet;
                    break;
                }
            }

            $displayHeaders = [];
            $displayIndexes = [];
            foreach ($selectedColumns as $key => $label) {
                $index = array_search(strtolower($key), $headers);
                if ($index === false && strtolower($key) === 'status') {
                    $displayHeaders[] = $label;
                    $displayIndexes[] = null;
                    continue;
                }
                if ($index !== false) {
                    $displayHeaders[] = $label;
                    $displayIndexes[] = $index;
                }
            }

            $amountCol = null;
            foreach ($displayIndexes as $idx) {
                if ($idx !== null && in_array($headers[$idx], ['totalamt','paid amount','amount','txn_amt','total amount','reqamount','amount bdt'])) {
                    $amountCol = $idx;
                    break;
                }
            }

            $totalAmount = 0;
            if ($amountCol !== null) {
                for ($i = 1; $i < count($data); $i++) {
                    $val = preg_replace('/[^0-9.\-]/', '', $data[$i][$amountCol]);
                    if (is_numeric($val)) $totalAmount += $val;
                }
            }
?>

            <div style="margin-bottom:30px;">
                <h3>File: <?= htmlspecialchars($file['filename']) ?>
                    (Bank: <?= htmlspecialchars($file['bank_name']) ?>, User: <?= htmlspecialchars($file['username']) ?>)
                </h3>

                <p><b>Unmatched Rows in This File:</b> <?= number_format($fileRowCount) ?></p>
                <p><b>Total TK Amount:</b> <?= number_format($totalAmount, 2) ?> </p>

                <div style="overflow-x:auto; margin-top:10px;">
                    <table style="width:100%; border-collapse:collapse; min-width:900px;">
                        <tr>
                            <?php foreach ($displayHeaders as $header): ?>
                                <th style="border:1px solid #ddd; padding:5px; background:#f5f5f5;"><?= htmlspecialchars($header) ?></th>
                            <?php endforeach; ?>
                        </tr>

                        <?php for ($i = 1; $i < count($data); $i++): ?>
                            <?php
                                $txnCell = $fData['txnCol'] !== null ? trim($data[$i][$fData['txnCol']]) : '';
                                $highlight = '';
                                if ($txnCell !== '') {
                                    if (isset($duplicateHighlightIds[$txnCell])) {
                                        if (!isset($seenTxnIds[$txnCell])) {
                                            $seenTxnIds[$txnCell] = true;
                                            $firstOccurrenceCount++; 
                                        } else {
                                            $highlight = 'background:#ffcccc;';
                                        }
                                    } else {
                                        $firstOccurrenceCount++; 
                                    }
                                } else {
                                    $firstOccurrenceCount++; 
                                }
                            ?>
                            <tr style="<?= $highlight ?>">
                                <?php foreach ($displayIndexes as $colIndex): ?>
                                    <?php
                                        $cell = $colIndex === null ? "" : $data[$i][$colIndex];
                                        if ($colIndex !== null && in_array($headers[$colIndex], ['totalamt','paid amount','amount','txn_amt','total amount','reqamount','amount bdt']) && is_numeric($cell)) {
                                            $cell = number_format((float)$cell, 2, '.', '');
                                        }
                                    ?>
                                    <td style="border:1px solid #ddd; padding:5px;"><?= htmlspecialchars($cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </table>
                </div>
            </div>

<?php
        endforeach;

        echo "<script>document.getElementById('total-uncolored').innerText = '".number_format($firstOccurrenceCount)."';</script>";
    }
}
?>
</div>
