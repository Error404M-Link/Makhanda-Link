<?php

include 'Error404Connection.php';

$ticket = null;

/* Check if a ticket ID was provided */
if (isset($_GET['incident_id'])) {

    $incident_id = intval($_GET['incident_id']);

    $sql = "SELECT incident_id, username, service_id, ward_id, address_id,
                   status_id, incident_description, location_description,
                   priority, date_reported, date_resolved, created_at
            FROM incident_ticket
            WHERE incident_id = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $incident_id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ticket Details</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: darkgreen;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: white;
            text-align: center;
        }

        .back-button {
            color: white;
            text-decoration: none;
        }

        .ticket-card {
            background-color: white;
            padding: 30px;
            margin: 20px auto;
            border-radius: 10px;
            max-width: 800px;
        }

        .ticket-card h3 {
            color: darkgreen;
        }

        .message {
            background-color: white;
            padding: 30px;
            margin: 20px auto;
            border-radius: 10px;
            max-width: 800px;
            text-align: center;
        }

        .details-button {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }

        .details-button:hover {
            background-color: green;
        }

    </style>

</head>

<body>

    <h1>Ticket Details</h1>

    <a class="back-button" href="ticketHistory.php">
        Back to Ticket History
    </a>

    <br><br>

    <?php if ($ticket !== null) { ?>

        <div class="ticket-card">

            <h3>
                Ticket #<?php echo $ticket['incident_id']; ?>
            </h3>

            <p>
                <strong>USERNAME:</strong>
                <?php echo $ticket['username']; ?>
            </p>

            <p>
                <strong>SERVICE ID:</strong>
                <?php echo $ticket['service_id']; ?>
            </p>

            <p>
                <strong>WARD:</strong>
                <?php echo $ticket['ward_id']; ?>
            </p>

            <p>
                <strong>ADDRESS ID:</strong>
                <?php echo $ticket['address_id']; ?>
            </p>

            <p>
                <strong>INCIDENT:</strong>
                <?php echo $ticket['incident_description']; ?>
            </p>

            <p>
                <strong>LOCATION:</strong>
                <?php echo $ticket['location_description']; ?>
            </p>

            <p>
                <strong>PRIORITY:</strong>
                <?php echo $ticket['priority']; ?>
            </p>

            <p>
                <strong>STATUS:</strong>
                <?php echo $ticket['status_id']; ?>
            </p>

            <p>
                <strong>DATE REPORTED:</strong>
                <?php echo $ticket['date_reported']; ?>
            </p>

            <p>
                <strong>DATE RESOLVED:</strong>
                <?php
                if (!empty($ticket['date_resolved'])) {
                    echo $ticket['date_resolved'];
                } else {
                    echo "Not resolved yet";
                }
                ?>
            </p>

            <p>
                <strong>CREATED AT:</strong>
                <?php echo $ticket['created_at']; ?>
            </p>

        </div>

    <?php } else { ?>

        <div class="message">

            <h3>No Ticket Found</h3>

            <p>
                There are currently no ticket details to display.
            </p>

            <a href="ticketDetails.php?ticketId=<?php echo $row['incident_id']; ?>">
    View Details
</a>
        </div>

    <?php } ?>

</body>

</html>

<?php

$conn->close();

?>