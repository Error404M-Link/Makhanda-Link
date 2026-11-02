<?php
require '../Error404Connection.php'

$complete = mysqli_query($conn,
"SELECT COUNT(*) AS TOTAL
FROM service_faults
WHERE status = 'completed'");
$complete= mysqli_fetch_assoc($complete);

$pending
 = mysqli_query($conn,
 "SELECT COUNT(*)  AS total
 FROM service_faults
 WHERE status = 'pending'");
 $pending = mysqli_fetch_assoc($pending);

$progress = mysqli_query( $conn,
    "SELECT COUNT(*) AS total
    FROM service_faults
    WHERE status = 'in progress'");
    $progress = mysqli_fetch_assoc($progress);
    
    $wards = mysqli_query($conn,
    "SELECT COUNT(*) as  total
    FROM Wards");
    $wards = mysqli_fetch_assoc($wards);

SELECT COUNT(*) AS total 
FROM notices
WHERE status='active'
    ?>



