<?php
$hostname = "is3-dev.ict.ru.ac.za";
$user ="G23G4435"; 
$password ="GanMas23!"; 
$dbname ="g23g4435";

$conn = new mysqli($hostname,$user,$password,$dbname);
if($conn -> connect_error) {
        die ("database connection failed:".$conn->connect_error);
}
?>
