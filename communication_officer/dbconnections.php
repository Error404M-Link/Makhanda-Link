<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
</head>
<body>
<?php
$hostname = "is3-dev.ict.ru.ac.za";
$user ="G23G4435"; 
$password ="GanMas23!"; 
$dbname ="g23g4435";

$conn = new mysqli($hostname,$user,$password,$dbname);
if($conn -> connect_error) {
        die ("database connection failed:".$connn->connect_error);
}
echo "database connection successfully";
?>
</body>
</html>