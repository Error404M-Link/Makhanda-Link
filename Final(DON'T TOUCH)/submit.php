<?php
include("dbconnections.php");

$title = $_POST["title"];
$notice_date = $_POST["notice_date"];
$message = $_POST["message"];
$ward = $_POST["ward"];

$sql = "INSERT INTO notices (ward, title, notice_date, message)
        VALUES ('$ward','$title','$notice_date','$message')";

$conn -> query($sql); 
header("Location: view_notices.php");
exit();

?>