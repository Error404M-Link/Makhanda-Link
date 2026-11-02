
<?php

$hostname = "is3-dev.ict.ru.ac.za";
$user = "G23M3001";
$password = "MlaLiz23!";
$dbname = "error404";   // Replace with your actual database name

$conn = new mysqli($hostname, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

echo "Database connection successfully established";

?>

