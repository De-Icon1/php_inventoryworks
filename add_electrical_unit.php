<?php
require_once 'assets/inc/config.php';

echo "Adding Electrical Unit to units table...\n\n";

// Check if Electrical Unit already exists
$check = $mysqli->query("SELECT unit_id FROM units WHERE unit_name = 'Electrical Unit'");

if($check->num_rows > 0){
    echo "✓ Electrical Unit already exists in units table\n";
} else {
    // Insert Electrical Unit
    $stmt = $mysqli->prepare("INSERT INTO units (unit_name) VALUES (?)");
    $unit_name = "Electrical Unit";
    $stmt->bind_param("s", $unit_name);
    
    if($stmt->execute()){
        echo "✓ Electrical Unit added successfully!\n";
    } else {
        echo "✗ Error: " . $mysqli->error . "\n";
    }
    $stmt->close();
}

echo "\nCurrent units in the system:\n";
echo "========================================\n";
$result = $mysqli->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
while($row = $result->fetch_assoc()){
    echo "- {$row['unit_name']}\n";
}

echo "\n✅ Done!\n";
?>
