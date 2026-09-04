<?php

session_start();

include "dbconnection.php";

if(isset($_SESSION['userId'])){
    header("Location: login.html");
    exist();
}

$userId = $_SESSION['userId'];

if(isset($_GET['ticketId'])){
    die("Ticket ID is missing.");
}

$ticketId = intval($_GET['ticketId']);

$sql = "SELECT * 
        FROM faultTickets
        WHERE ticketId = ?
        AND userId = ?";

    $stmt = $conn->prepare($sql);

    if ($result->num_rows ==0 ){
        die("Ticket not found.");
    }

$ticket = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
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

            <?php

            if(!empty($ticket['imagePath'])) {
                ?>


<h3> Evidence Image </h3>
<img src="<?php echo $ticket['imagePath']; ?>"
            }
    










</body>
</html>


