<?php
require_once 'assets/inc/config.php';

echo "Adding 'vc' and 'transport' roles to users table...\n\n";

// Alter the role enum to include vc and transport
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','director','supervisor','storekeeper','vc','transport') DEFAULT 'storekeeper'";

if($mysqli->query($sql)){
    echo "✓ Successfully added 'vc' and 'transport' to role options\n\n";
    
    // Now update the users
    $mysqli->query("UPDATE users SET role = 'vc' WHERE user_id = 2");
    echo "✓ Updated VC user role\n";
    
    $mysqli->query("UPDATE users SET role = 'transport' WHERE user_id = 3");
    echo "✓ Updated Transport user role\n";
    
    echo "\n=== Verification ===\n";
    $res = $mysqli->query("SELECT user_id, username, full_name, role FROM users");
    while($r = $res->fetch_assoc()){
        echo "ID: {$r['user_id']} | Username: {$r['username']} | Role: {$r['role']}\n";
    }
    
    echo "\n========================================\n";
    echo "LOGIN CREDENTIALS FOR INDEX.PHP\n";
    echo "========================================\n\n";
    
    echo "VICE CHANCELLOR:\n";
    echo "  Username: vc\n";
    echo "  Password: vc123\n";
    echo "  → Redirects to: vc_dashboard.php\n\n";
    
    echo "TRANSPORT OFFICER:\n";
    echo "  Username: transport\n";
    echo "  Password: transport123\n";
    echo "  → Redirects to: transport_dashboard.php\n\n";
    
    echo "✅ All set! Users can now login at index.php\n";
} else {
    echo "❌ Error: " . $mysqli->error . "\n";
}
?>
