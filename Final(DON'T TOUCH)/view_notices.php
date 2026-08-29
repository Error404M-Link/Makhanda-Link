<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward councilor's Update</title>
    <link rel="stylesheet" href="view_notices.css">
</head>
<body>
        <nav class="navbar"> 
        <div class="logo">
            <img src="MakanaLogo.svg" alt="Makhanda logo">
        </div> 
        <ul class="nav-links">
            <li><a href="#" class="link"> Home </a></li>
            <li><a href="#" class="link"> Fault </a></li>
            <li><a href="#" class="link"> About us </a></li>
            <li><a href="#" class="link"> Notices  </a></li>
            <div id="navbuttons">
                <li><a href="#" class="btn"> Login </a></li>
                <li><a href="#" class="btn"> Sign up </a></li>
            </div>
        </ul> 
    </nav>
    <hr>

    <div class="backNavWrapper">
            <div class="backNavContainer">
                <a href="javascript:history.back()" class="backBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>


<br>
<br>

<form method="GET" action="view_notices.php">
<label>Select Ward:</label>
<select name="ward" id="ward">
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

if ($ward == "") {
    $sql = "SELECT * FROM notices 
            ORDER BY notice_date DESC";
} else {
    $sql = "SELECT * FROM notices 
            WHERE ward = '$ward' 
            ORDER BY notice_date DESC";
}

$results = $conn->query($sql); 
?>

<div class="notice-container">

<?php
while ($row = $results->fetch_assoc()) {
?>

    <div class="notice-card">

        <h3><?php echo $row['ward']; ?></h3>

        <h3><?php echo $row['title']; ?></h3>

        <h3><?php echo $row['notice_date']; ?></h3>

        <p><?php echo $row['message']; ?></p>

    </div>

<?php
}
?>

</div>

