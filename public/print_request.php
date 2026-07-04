<?php
// ensure PHP open tag at file start; require init bootstrap
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
use App\DB;
$pdo = DB::getPDO();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT r.*, m.name as machine_name, u.username as requester_name FROM requests r JOIN machines m ON m.id = r.machine_id JOIN users u ON u.id = r.requester_id WHERE r.id = :id');
$stmt->execute([':id'=>$id]);
$r = $stmt->fetch();
if (!$r) { echo 'Not found'; exit; }
// If PDF requested, generate with Dompdf
if (!empty($_GET['pdf'])) {
  if (class_exists('\Dompdf\\Dompdf')) {
    $html = '<html><head><meta charset="utf-8"><style>body{font-family:Arial,Helvetica,sans-serif} table{width:100%;border-collapse:collapse} td,th{padding:6px;border:1px solid #ddd}</style></head><body>';
    $html .= '<h1>Privilege Access Request Form</h1><table>';
    $html .= '<tr><th>Request ID</th><td>'.$r['id'].'</td></tr>';
    $html .= '<tr><th>Requester</th><td>'.htmlspecialchars($r['requester_name']).'</td></tr>';
    $html .= '<tr><th>Machine</th><td>'.htmlspecialchars($r['machine_name']).'</td></tr>';
    $html .= '<tr><th>Reason</th><td>'.nl2br(htmlspecialchars($r['reason'])).'</td></tr>';
    $html .= '<tr><th>Status</th><td>'.htmlspecialchars($r['status']).'</td></tr>';
    $html .= '</table></body></html>';
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('request_'.$r['id'].'.pdf', ['Attachment'=>0]);
    exit;
  }
}
?>
<!doctype html>
<html><head><title>Request #<?=$r['id']?></title>
<style>body{font-family:Arial,Helvetica,sans-serif} .sheet{width:800px;margin:0 auto} table{width:100%;border-collapse:collapse} td,th{padding:6px;border:1px solid #ddd}</style>
</head><body>
<div class="sheet">
  <h1>Privilege Access Request Form</h1>
  <table>
    <tr><th>Request ID</th><td><?=$r['id']?></td></tr>
    <tr><th>Requester</th><td><?=htmlspecialchars($r['requester_name'])?></td></tr>
    <tr><th>Machine</th><td><?=htmlspecialchars($r['machine_name'])?></td></tr>
    <tr><th>Reason</th><td><?=nl2br(htmlspecialchars($r['reason']))?></td></tr>
    <tr><th>Status</th><td><?=htmlspecialchars($r['status'])?></td></tr>
  </table>
  <p>Signature: ________________________</p>
</div>
<script nonce="<?=\App\Security::cspNonce()?>">window.print()</script>
</body></html>
