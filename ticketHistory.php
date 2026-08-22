<?php

session_start();

include "dbconnection.php";

if(isset($_SESSION['userId'])){
    header("Location: login.html");
    exist();
}

$userId = $_SESSION['userId'];

$sql = "SELECT ticketId, wardId, category, locationDescription, description, satus, createdAt FROM faultTickets
            WHERE userId = ? ORDER BY createdAt DESC";



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   

    <title>Ticket History</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f5;
        }

        .ticket-card {
            background-color: white;
            padding: 30px;
            margin: 300px;
            border-radius: 10px;
        }

    </style>
</head>
<body>
    <h1>Ticket History</h1>
    <p> REPORTED FAULTS </p>
     <a href="residentDashboard.php">Back to Dashboard</a>

     <br><br>

     <?php

     if ($result-> num_rows > 0) {
        while ($ticket =$result->fetch_assoc)

            ?>

            <hr><h3> Ticket #<?php echo $ticket ['ticketId']; ?> </h3>

            <p> <strong>WARD:</strong>
            <?php echo $ticket['wardId']; ?> </p>

            <p> <strong>CATEGORY:</strong>
            <?php echo $ticket['category']; ?> </p>

            <p> <strong>LOCATION:</strong>
            <?php echo $ticket['locationDescription']; ?> </p>

            <p> <strong>Description:</strong>
            <?php echo $ticket['description']; ?> </p>

            <p> <strong>Status:</strong>
            <?php echo $ticket['status']; ?> </p>

            <p> <strong>Date Reported:</strong>
            <?php echo $ticket['createdAt']; ?> </p>

            <a href="ticketDetails.php?ticketId=<?php echo $ticket['ticketId']; ?>">
            View Details </a> 

            <br><br>

            <?php

     }

     $stmt->close();
     $conn->close();

     ?>





</body>
</html>