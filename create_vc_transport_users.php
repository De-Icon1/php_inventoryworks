<?php
require_once 'assets/inc/config.php';

echo "Creating user accounts...\n\n";

// Check users table structure
$res = $mysqli->query("DESCRIBE users");
echo "Users table columns: ";
while($r = $res->fetch_assoc()){
    echo $r['Field'] . " ";
}
echo "\n\n";

// Vice Chancellor account
$vc_username = "vc";
$vc_password = "vc123"; // You can change this
$vc_fullname = "Vice Chancellor";
$vc_role = "vc";
$vc_hash = password_hash($vc_password, PASSWORD_DEFAULT);

// Check if VC user exists
$check = $mysqli->query("SELECT user_id FROM users WHERE username = '$vc_username'");
if($check->num_rows > 0){
    echo "❌ Vice Chancellor user already exists!\n";
} else {
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $vc_username, $vc_hash, $vc_fullname, $vc_role);
    if($stmt->execute()){
        echo "✓ Vice Chancellor account created successfully!\n";
        echo "  Username: $vc_username\n";
        echo "  Password: $vc_password\n";
        echo "  Role: $vc_role\n\n";
    } else {
        echo "❌ Error creating VC account: " . $mysqli->error . "\n";
    }
    $stmt->close();
}

// Transport Officer account
$transport_username = "transport";
$transport_password = "transport123"; // You can change this
$transport_fullname = "Transport Officer";
$transport_role = "transport";
$transport_hash = password_hash($transport_password, PASSWORD_DEFAULT);

// Check if Transport user exists
$check = $mysqli->query("SELECT user_id FROM users WHERE username = '$transport_username'");
if($check->num_rows > 0){
    echo "❌ Transport user already exists!\n";
} else {
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $transport_username, $transport_hash, $transport_fullname, $transport_role);
    if($stmt->execute()){
        echo "✓ Transport Officer account created successfully!\n";
        echo "  Username: $transport_username\n";
        echo "  Password: $transport_password\n";
        echo "  Role: $transport_role\n\n";
    } else {
        echo "❌ Error creating Transport account: " . $mysqli->error . "\n";
    }
    $stmt->close();
}

echo "\n=== All Users in System ===\n";
$res = $mysqli->query("SELECT user_id, username, full_name, role FROM users ORDER BY role, username");
while($r = $res->fetch_assoc()){
    echo "ID: {$r['user_id']} | Username: {$r['username']} | Name: {$r['full_name']} | Role: {$r['role']}\n";
}

echo "\n✅ Done! You can now login with the credentials above.\n";
?>
