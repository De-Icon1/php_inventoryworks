<?php
require_once 'assets/inc/config.php';

$res = $mysqli->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
if(!$res){
    echo "Query error: " . $mysqli->error . "\n";
    exit;
}

echo "Units in database:\n";
while($r = $res->fetch_assoc()){
    echo "- ({$r['unit_id']}) {$r['unit_name']}\n";
}
?>