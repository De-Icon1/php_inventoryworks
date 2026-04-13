<?php
require_once 'assets/inc/config.php';

echo "Updating user roles...\n\n";

// Update VC role
$mysqli->query("UPDATE users SET role = 'vc' WHERE username = 'vc'");
echo "✓ Updated Vice Chancellor role\n";

// Update Transport role
$mysqli->query("UPDATE users SET role = 'transport' WHERE username = 'transport'");
echo "✓ Updated Transport Officer role\n";

echo "\n=== Updated Users ===\n";
$res = $mysqli->query("SELECT user_id, username, full_name, role FROM users ORDER BY user_id");
while($r = $res->fetch_assoc()){
    echo "ID: {$r['user_id']} | Username: {$r['username']} | Name: {$r['full_name']} | Role: {$r['role']}\n";
}

echo "\n========================================\n";
echo "LOGIN CREDENTIALS\n";
echo "========================================\n\n";

echo "VICE CHANCELLOR:\n";
echo "  URL: http://localhost/works/index.php\n";
echo "  Username: vc\n";
echo "  Password: vc123\n";
echo "  → Redirects to: vc_dashboard.php\n\n";

echo "TRANSPORT OFFICER:\n";
echo "  URL: http://localhost/works/index.php\n";
echo "  Username: transport\n";
echo "  Password: transport123\n";
echo "  → Redirects to: transport_dashboard.php\n\n";

echo "✅ Done! Users can now login.\n";
?>
