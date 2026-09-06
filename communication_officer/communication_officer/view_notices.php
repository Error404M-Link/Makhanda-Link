<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View notices</title>
    <link rel="stylesheet" href="view_notices.css">
</head>
<body>
    <hr>

<h2> Ward public Notice Board </h2>
<br>
<br>

<form method="GET" action="view_notices.php">
<label>Select Ward:</Select>
<select name="ward">
    <option value="">All Wards</option>
    <option value="1">Ward 1</option>
    <option value="2">Ward 2</option>
    <option value="3">Ward 3</option>
    <option value="4">Ward 4</option>
    <option value="5">Ward 5</option>
    <option value="6">Ward 6</option>
    <option value="7">Ward 7</option>
    <option value="8">Ward 8</option>
    <option value="9">Ward 9</option>
    <option value="10">Ward 10</option>
    <option value="11">Ward 11</option>
    <option value="12">Ward 12</option>
    <option value="13">Ward 13</option>
    <option value="14">Ward 14</option>
</select> 
<button type="submit">View Notice</button> 
</form>
<hr>
</body> 

<?php
include("dbconnections.php");

$ward = $_GET["ward"] ?? ""; 

if ($ward == ""){
    $sql = "SELECT * FROM notices 
    ORDER BY notice_date DESC";
}else {
    $sql ="SELECT * FROM notices 
    WHERE ward = '$ward' 
    ORDER BY notice_date DESC";
}

$results = $conn->query($sql); 
 

while ($row = $results -> fetch_assoc()){
    echo "<h3>" . $row['ward'] . "</h3>";
    echo "<h3>" . $row['title'] . "</h3>";
    echo "<h3>" . $row['notice_date'] . "</h3>";
    echo "<h3>" . $row['message'] . "</h3>";
    echo "<hr>";
}
?>