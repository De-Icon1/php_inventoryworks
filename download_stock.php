<?php
include('assets/inc/config.php');
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=stock_report_".date('Ymd').".xls");

echo "Item\tCategory\tUnit\tQuantity\tLast Updated\n";
$sql = "SELECT it.item_name, c.name AS category, it.unit, COALESCE(sb.quantity,0) AS qty, sb.last_updated FROM items it LEFT JOIN categories c ON it.category_id=c.category_id LEFT JOIN stock_balance sb ON it.item_id=sb.item_id ORDER BY it.item_name";
$res = $mysqli->query($sql);
while($r = $res->fetch_assoc()){
    echo "{$r['item_name']}\t{$r['category']}\t{$r['unit']}\t{$r['qty']}\t{$r['last_updated']}\n";
}
exit;