<?php
$hostname = "is3-dev.ict.ru.ac.za";
$user ="G24S7222"; 
$password ="SomNth!"; 
$dbname ="g24s7222";

$conn = new mysqli($hostname,$user,$password,$dbname);
if($conn -> connect_error) {
        die ("database connection failed:".$conn->connect_error);
}
?>
