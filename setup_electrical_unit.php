<?php
require_once 'assets/inc/config.php';

echo "Setting up Electrical Unit...\n\n";

// 1. Add electrical role to ENUM
echo "1. Adding 'electrical' role to users table...\n";
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','director','supervisor','storekeeper','vc','transport','electrical') DEFAULT 'storekeeper'";
if($mysqli->query($sql)){
    echo "   ✓ Role added successfully\n\n";
} else {
    echo "   Note: " . $mysqli->error . "\n\n";
}

// 2. Create Electrical category if it doesn't exist
echo "2. Creating 'Electrical' category...\n";
$check = $mysqli->query("SELECT category_id FROM categories WHERE name = 'Electrical'");
if($check->num_rows == 0){
    $mysqli->query("INSERT INTO categories (name, description) VALUES ('Electrical', 'Electrical appliances and components')");
    echo "   ✓ Category created\n\n";
} else {
    echo "   - Category already exists\n\n";
}

// 3. Create electrical user account
echo "3. Creating electrical user account...\n";
$elec_username = "electrical";
$elec_password = "electrical123";
$elec_fullname = "Electrical Officer";
$elec_role = "electrical";
$elec_hash = password_hash($elec_password, PASSWORD_DEFAULT);

$check = $mysqli->query("SELECT user_id FROM users WHERE username = '$elec_username'");
if($check->num_rows > 0){
    echo "   - User already exists, updating role...\n";
    $mysqli->query("UPDATE users SET role = 'electrical' WHERE username = '$elec_username'");
} else {
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $elec_username, $elec_hash, $elec_fullname, $elec_role);
    $stmt->execute();
    $stmt->close();
    echo "   ✓ User created successfully\n";
}

echo "\n========================================\n";
echo "ELECTRICAL UNIT LOGIN\n";
echo "========================================\n";
echo "URL: http://localhost/works/index.php\n";
echo "Username: electrical\n";
echo "Password: electrical123\n";
echo "Dashboard: electrical_dashboard.php\n\n";

echo "✅ Electrical Unit setup complete!\n";
?>
