<?php
$hash = '$2y$10$QhrwE0IZHmkxwbbJyN7pZOuQZaPLLKvU/LvSicfT3Qrzhl6WPc0zq';

if(password_verify("admin123", $hash)){
    echo "MATCHES";
} else {
    echo "NO MATCH";
}