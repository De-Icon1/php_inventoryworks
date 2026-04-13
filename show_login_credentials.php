<?php
require_once 'assets/inc/config.php';

echo "========================================\n";
echo "  LOGIN CREDENTIALS FOR INDEX.PHP\n";
echo "========================================\n\n";

$res = $mysqli->query("SELECT user_id, username, full_name, role FROM users WHERE role IN ('transport', 'vc') ORDER BY role");

while($r = $res->fetch_assoc()){
    echo strtoupper($r['role']) . " ACCOUNT:\n";
    echo "  Login Page: index.php\n";
    echo "  Username: " . $r['username'] . "\n";
    echo "  Password: " . $r['username'] . "123\n";
    echo "  Full Name: " . $r['full_name'] . "\n";
    echo "  Role: " . $r['role'] . "\n";
    echo "  Redirects to: " . $r['role'] . "_dashboard.php\n";
    echo "\n";
}

echo "========================================\n";
echo "HOW IT WORKS:\n";
echo "========================================\n";
echo "1. Go to: http://localhost/works/index.php\n";
echo "2. Enter username and password\n";
echo "3. System automatically redirects based on role:\n";
echo "   - Role 'transport' → transport_dashboard.php\n";
echo "   - Role 'vc' → vc_dashboard.php\n";
echo "\n";
?>
